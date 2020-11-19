var QRHEAD = document.getElementsByTagName('head')[0];
var QRSCRIPT = document.createElement('script');
QRSCRIPT.setAttribute('src','/themes/yutui/js/jquery.qrcode.min.js');
QRSCRIPT.setAttribute('type','text/javascript');
window.Mqrcode = null;
QRSCRIPT.onload = function(){
	QRSCRIPT.onloadDone = true;
	QRAUTOINIT();
};
QRHEAD.appendChild(QRSCRIPT);

function QRAUTOINIT(){
	Mqrcode = {
			callback:function(img){
				alert(img);
			},
			width:200,
			height:200,
			bgImg:"",
			link:location.href,
			position:{w:10,h:10},
			pluginText:[],
			pluginImg:[],
			typeNumber:4,
			colorDark:"#000000",
			colorLight:"#ffffff",
			correctLevel:0,
			canvas:null,
			ctx:null,
			after:null,
			
			success:function(img){
				$("#myqrcode").remove();
				Mqrcode.callback(img);
			},
			/*
			 * config  array(callback,bgImg,link)
			 */
			makeCode: function(config,reset=false){
				
				this.initConfig(config);
				
				this.makeQrcode();
			},
			initConfig:function(config){
				this.callback=function(img){
					alert(img);
				};
			
				this.width=200;
				this.height=200;
				this.bgImg="";
				this.link=location.href;
				this.position={w:10,h:10};
				this.pluginText=[];
				this.pluginImg=[];
				this.typeNumber=4;
				this.colorDark="#000000";
				this.colorLight="#ffffff";
				this.correctLevel=0;
				this.canvas=null;
				this.ctx=null;
				
				if(config!=undefined){
					for(key in config){
						this[key]=config[key];
					}
				}
			},

			drawS:function(x1, y1, x2, y2, x3, y3, color, type,ctx){
				ctx.beginPath();
			    ctx.moveTo(x1, y1);
			    ctx.lineTo(x2, y2);
			    ctx.lineTo(x3, y3);
			    ctx[type + 'Style'] = color;
			    ctx.closePath();
			    ctx[type]();
			},
			makeQrcode:function(){
				$("body").append("<div id='myqrcode' style='display:none'></div>");
				var qrcode = $('#myqrcode').qrcode({
					render : "canvas",
					width: this.width,
					height: this.height,
					text: this.link,
					correctLevel : 0,
					background : "#ffffff", 
					foreground : "#000000"
					});

				var canvas = qrcode.find('canvas').get(0);
				$("#myqrcode").append("<img src='"+canvas.toDataURL('image/png')+"'>");
				
				var qrimg = $("#myqrcode").find("img")[0];
				qrimg.setAttribute("crossOrigin",'Anonymous');
				
				qrimg.onload = function(){
					Mqrcode.canvas =document.createElement('canvas');

					if(Mqrcode.bgImg==""){
						return  Mqrcode.success(qrimg.src);
					}
					
				 	var bgImg = new Image();
				 	bgImg.setAttribute("crossOrigin",'Anonymous');
				 	bgImg.src= Mqrcode.bgImg;
					bgImg.onload = function(){
				 		Mqrcode.canvas.width = bgImg.width;
				 		Mqrcode.canvas.height = bgImg.height;
						Mqrcode.ctx=Mqrcode.canvas.getContext('2d'); //getContext()
						
						Mqrcode.ctx.drawImage(bgImg,0,0,bgImg.width,bgImg.height);
						Mqrcode.ctx.drawImage(qrimg,Mqrcode.position.l,Mqrcode.position.t,qrimg.width,qrimg.height);
						
						Mqrcode.drawPlugin();
					}
				}

			},
			drawPlugin:function(){
				if(this.pluginImg.length>0){
					return this.drawPluginImg(0);
				}else{
					if(this.pluginText.length>0){
						return this.drawPluginText();
					}
				}
				var newimg = Mqrcode.canvas.toDataURL("image/png");
				return Mqrcode.success(newimg);
			},
			drawPluginImg:function(index){
				var img = new Image();
				img.setAttribute("crossOrigin",'Anonymous');
				img.src= this.pluginImg[index].src;
			 	var circle = this.pluginImg[index].circle;

				img.onload = function(){
					var w = Mqrcode.pluginImg[index].w;
					var h=  Mqrcode.pluginImg[index].h;
					if(w===undefined)w = img.width;
					if(h===undefined)h = img.height;
					
					if(circle){
						Mqrcode.ctx.save();
						var r = w/2;
						Mqrcode.ctx.arc(Mqrcode.pluginImg[index].l+r,Mqrcode.pluginImg[index].t+r,r,0,2*Math.PI);
						Mqrcode.ctx.clip();
						Mqrcode.ctx.drawImage(img, Mqrcode.pluginImg[index].l, Mqrcode.pluginImg[index].t,w,h);
						Mqrcode.ctx.restore();
					}else{
						Mqrcode.ctx.drawImage(img, Mqrcode.pluginImg[index].l, Mqrcode.pluginImg[index].t,w,h);

					}
					
					
					
					index++;
					if(index>=Mqrcode.pluginImg.length){
						if(Mqrcode.pluginText.length>0){
							return Mqrcode.drawPluginText();
						}else{
							var newimg = Mqrcode.canvas.toDataURL("image/png");
							return Mqrcode.success(newimg);
						}
					}else{
						Mqrcode.drawPluginImg(index);
					}
				};
			},
			drawPluginText:function(){
				for(key in this.pluginText){
					var text = this.pluginText[key];
					
					if(text.bgcolor!=undefined){
						this.ctx.fillStyle = text.bgcolor;
						if(text.alpha!=undefined){
							this.ctx.globalAlpha=text.alpha;
						}
						this.ctx.fillRect(text.l,text.t,text.w,text.h);
						if(text.alpha!=undefined){
							this.ctx.globalAlpha=1.0;
						}
					}else{
						this.ctx.font=text.font;
						this.ctx.textAlign = text.align;
						this.ctx.fillStyle = text.style;
						if(text.w==undefined||text.w==0){
							this.ctx.fillText(text.text,text.l,text.t);
						}else{
							var texttop = text.t;
							var start=0;
							var th = 20;
							if(text.h!=undefined) th = text.h;
							if(this.ctx.measureText(text.text[0]).width>text.w){continue;}
							for(let i=1;i<text.text.length+1;i++){
								var tempstr = text.text.substring(start,i);
								if(this.ctx.measureText(tempstr).width>text.w){
									this.ctx.fillText(text.text.substring(start,i-1),text.l,texttop);
									texttop += th;
									start=i-1;
								}
							}
							
							if(start==0){
								this.ctx.fillText(text.text,text.l,text.t);
							}else{
								this.ctx.fillText(text.text.substring(start),text.l,texttop);
							}
						}
					}
				}
				if(this.after!=undefined){
					return this.after(this);
				}
				var newimg = this.canvas.toDataURL("image/png");
				return Mqrcode.success(newimg);
				
			}
		}
}



