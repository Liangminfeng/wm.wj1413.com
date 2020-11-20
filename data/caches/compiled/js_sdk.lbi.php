
	<?php if ($this->_var['is_wechat']): ?>
	<script type="text/javascript" src="__PUBLIC__/js/jquery.min.js" ></script>
	<script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
	<script type="text/javascript">
	    // 分享内容
	    var shareContent = {
	        title: '<?php echo $this->_var['share_data']['title']; ?>',
	        desc: '<?php echo $this->_var['share_data']['desc']; ?>',
	        link: '<?php echo $this->_var['share_data']['link']; ?>',
	        imgUrl: '<?php echo $this->_var['share_data']['img']; ?>',
	    };
	    $(function(){
	        var url = window.location.href;
	        var jsConfig = {
	            debug: false,
				jsApiList : [ 'updateAppMessageShareData', 'updateTimelineShareData',
					'onMenuShareWeibo']
	        };
	        $.post("<?php echo url('api/jssdk');?>", {url: url}, function (res) {
	            if(res.status == 200){
	                jsConfig.appId = res.data.appId;
	                jsConfig.timestamp = res.data.timestamp;
	                jsConfig.nonceStr = res.data.nonceStr;
	                jsConfig.signature = res.data.signature;
	                // 配置注入
	                wx.config(jsConfig);
	                // 事件注入
	                wx.ready(function () {
	                	wx.updateAppMessageShareData(shareContent);
						wx.updateTimelineShareData(shareContent);
						wx.onMenuShareWeibo(shareContent);
	                });
	            }
	        }, 'json');
	    })
	</script>
	<?php endif; ?>