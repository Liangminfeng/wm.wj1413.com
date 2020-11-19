jQuery.getScript("/themes/yutui/js/qiniu.min.js");
window.QiniuManager = {
	QiniuConfig :null,
	QINIU_FILEINPUT_DIV : null,
	config : {
		multiple:false,
		size:100,
		number:6,
		error:function(msg){alert(msg);return;},
		callback:function(url){alert(url);},
		before:function(obj){},
	},
	initConfig:function(config){
		if(config!=undefined){
			for(key in config){
				this.config[key]=config[key];
			}
		}
	},
	
	QiniuUpload : function(config){
		this.initQiniuConfig(false,config);
		
		if(this.QINIU_FILEINPUT_DIV ==null){
			this.QINIU_FILEINPUT_DIV = "QINIUDIV"+(new Date().getTime());
			var multiple = "";
			if(this.config.multiple){multiple="  multiple='multiple'";}
			jQuery("body").append("<div id='"+this.QINIU_FILEINPUT_DIV+"' style='display:none'><input type='file' "+multiple+"></div>");
			jQuery("#"+this.QINIU_FILEINPUT_DIV).find("input").on("change",function(){
			    if(this.files.length>QiniuManager.config.number){
			    	return QiniuManager.config.error("files can't be more than "+QiniuManager.config.number);
			    }
			    for (var i = 0; i < this.files.length; i++) {
			    	QiniuManager.QiniuUploadProgress(this.files[i]);
				}
				
			});
		}
		jQuery("#"+this.QINIU_FILEINPUT_DIV).find("input").click();
		
	},
	
	destoryQiniuDiv:function(){
		jQuery("#"+this.QINIU_FILEINPUT_DIV).remove();
		this.QINIU_FILEINPUT_DIV = null;
	},
	
	initQiniuConfig:function(async,config){
		this.initConfig(config);
		
		if(this.QiniuConfig == null){
			jQuery.ajax({
				url: "/index.php?c=qiniu&a=token", 
				dataType:"json",
				async : async,
				success: function(res){
				    var token = res.uptoken;
				    var domain = res.domain;
				    var config = {
				      useCdnDomain: true,
				      disableStatisticsReport: false,
				      retryCount: 5,
				      region: res.region
				    };
				    var putExtra = {
				      fname: "",
				      params: {},
				      mimeType: null
				    };
				    QiniuManager.QiniuConfig = {token:token,domain:domain,config:config,putExtra:putExtra,region:res.region};
				 }
			});
		}
	},
	  
	  
	  
	QiniuUploadProgress:function(file,config) {
		this.initQiniuConfig(false,config);
	  // 切换tab后进行一些css操作
	    var observable;
	    if (file) {
	      var key = file.name;
	      key = this.createNewKeyForUpload(key);
	      // 设置next,error,complete对应的操作，分别处理相应的进度信息，错误信息，以及完成后的操作
	      var error = function(err) {
	    	  QiniuManager.destoryQiniuDiv();
	        console.log(err);
	        alert("上传出错")
	      };
	
	      var complete = function(res) {
	    	  QiniuManager.destoryQiniuDiv(); 
	    	  var resultfile = QiniuManager.QiniuConfig.domain+"/"+res.key;
	    	  QiniuManager.config.callback(resultfile);
	      };
	
	      var next = function(response) {
	      };
	
	      var subObject = { 
	        next: next,
	        error: error,
	        complete: complete
	      };
	      var subscription;
	      // 调用sdk上传接口获得相应的observable，控制上传和暂停
	      observable = qiniu.upload(file, key, this.QiniuConfig.token, this.QiniuConfig.putExtra, this.QiniuConfig.config);
	      observable.subscribe(subObject);
	    }
	},
	
	createNewKeyForUpload:function(file){
	    //验证上传的文件是否是excel文件  
	    var point = file.lastIndexOf(".");   
	    var type = file.substr(point);
	    var date = new Date();
	    date = date.getFullYear()+""+date.getMonth()+""+date.getDay();
	    var key = date+"/"+(new Date().getTime())+Math.floor((Math.random()*10)+1)+type;
	    return key;
	},
	
	uploadB64:function(dataurl,config) {
		this.initQiniuConfig(false,config);
		var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
        pic = arr[1];
	    //注意这个url,可以指定key(文件名), mimeType(文件类型)
		
	    var url = "http://upload-"+this.QiniuConfig.region+".qiniu.com/putb64/-1";
	    var xhr = new XMLHttpRequest();
	    xhr.onreadystatechange=function(){
	        if (xhr.readyState==4){
	            //这里可以判断图片上传成功,而且可以通过responseText获取图片链接
	            var data = JSON.parse(xhr.responseText)
	            var resultfile = QiniuManager.QiniuConfig.domain+"/"+data.key;
		    	QiniuManager.config.callback(resultfile);
	            //图片链接就是yourcdnpath.xx/data.key
	        }
	    }
	    xhr.open("POST", url, true); 
	    xhr.setRequestHeader("Content-Type", "application/octet-stream"); 
	    xhr.setRequestHeader("Authorization", "UpToken "+this.QiniuConfig.token); 
	    xhr.send(pic);
	}
}