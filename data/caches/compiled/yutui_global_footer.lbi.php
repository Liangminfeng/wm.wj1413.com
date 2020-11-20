<div class="s-height"></div>

<footer class="footer-nav dis-box" style="line-height: 15px">
				<?php if ($_SESSION['user_vip'] > 0): ?>
				<a href="<?php echo url('index/index');?>" class="box-flex nav-list<?php if ($this->_var['footer_index'] == 'index'): ?> active<?php endif; ?>">
					<i class="nav-box i-home"></i><span><?php echo $this->_var['lang']['home']; ?></span>
				</a>
				<a href="<?php echo url('article/article_index');?>" class="box-flex nav-list<?php if ($this->_var['footer_index'] == 'dongtai'): ?> active<?php endif; ?>">
					<i class="nav-box i-flow"></i><span><?php echo $this->_var['lang']['dongtai']; ?></span>
				</a>
				<a href="<?php echo url('article/index');?>" class="box-flex nav-list<?php if ($this->_var['footer_index'] == 'article'): ?> active<?php endif; ?>">
					<i class="nav-box i-cate"></i><span><?php echo $this->_var['lang']['hot_acticle']; ?></span>
				</a>
				<a href="<?php echo url('user/business_card');?>" class="box-flex nav-list<?php if ($this->_var['footer_index'] == 'affiliate'): ?> active<?php endif; ?>">
					<i class="nav-box i-shopcar"></i><span><?php echo $this->_var['lang']['mini_web']; ?></span>
				</a>
				<?php endif; ?>
				<a href="<?php echo url('user/index');?>" class="box-flex nav-list<?php if ($this->_var['footer_index'] == 'user'): ?> active<?php endif; ?>">
					<i class="nav-box i-user"></i><span><?php echo $this->_var['lang']['my']; ?></span>
				</a>
</footer>
<?php if ($this->_var['is_wechat']): ?>
<script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
<script type="text/javascript">
	var shareline = '<?php echo empty($this->_var['share_link']) ? $this->_var['share_data']['"link"'] : $this->_var['share_link']; ?>';
	var sharehref = shareline == '' ? location.href : shareline;
	var shareTitle = "<?php echo empty($this->_var['share_title']) ? $this->_var['share_data']['"title"'] : $this->_var['share_title']; ?>";
	var shareDesc = "<?php echo empty($this->_var['share_description']) ? $this->_var['share_data']['"desc"'] : $this->_var['share_description']; ?>";
	var sharePic = "<?php echo empty($this->_var['share_pic']) ? $this->_var['share_data']['"img"'] : $this->_var['share_pic']; ?>";
	// 分享内容
	var shareContent = {
		title : shareTitle,
		desc : shareDesc,
		link : sharehref,
		imgUrl : sharePic,
	};
	$(function() {
		var url = window.location.href;
		var jsConfig = {
			debug : false,
			jsApiList : [ 'updateAppMessageShareData', 'updateTimelineShareData',
				'onMenuShareWeibo']
		};
		$.post("<?php echo url('api/jssdk');?>", {
			url : url
		}, function(res) {
			if (res.status == 200) {
				jsConfig.appId = res.data.appId;
				jsConfig.timestamp = res.data.timestamp;
				jsConfig.nonceStr = res.data.nonceStr;
				jsConfig.signature = res.data.signature;
				// 配置注入
				wx.config(jsConfig);
				// 事件注入
				wx.ready(function() {
					wx.updateAppMessageShareData(shareContent);
					wx.updateTimelineShareData(shareContent);
					wx.onMenuShareWeibo(shareContent);
				});
			}
		}, 'json');
	})
</script>
<?php endif; ?>