<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <meta name="format-detection" content="telephone=no">
    <?php if ($this->_var['auto_redirect']): ?>
    <meta http-equiv="refresh" content="3;URL=<?php echo $this->_var['message']['back_url']; ?>" />
    <?php endif; ?>
    <meta name="description" content="<?php echo $this->_var['meta_description']; ?>"/>
    <meta name="keywords" content="<?php echo $this->_var['meta_keywords']; ?>"/>
    <title><?php echo $this->_var['page_title']; ?></title>
    <link rel="stylesheet" href="__PUBLIC__/bootstrap/css/bootstrap.min.css?v=<?php echo $this->_var['v']; ?>">
    <link rel="stylesheet" href="__PUBLIC__/bootstrap/css/font-awesome.min.css?v=<?php echo $this->_var['v']; ?>">
    <link rel="stylesheet" href="__PUBLIC__/swiper/css/swiper.min.css?v=<?php echo $this->_var['v']; ?>"/>
    <link rel="stylesheet" href="__TPL__/statics/css/ectouch.css?v=<?php echo $this->_var['v']; ?>" />
    <link rel="stylesheet" href="__TPL__/css/photoswipe.css?v=<?php echo $this->_var['v']; ?>">
    <link rel="stylesheet" href="__TPL__/css/style.css?v=<?php echo $this->_var['v']; ?>">
    <script type="text/javascript" src="__TPL__/js/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="__TPL__/js/ectouch.js?v=<?php echo $this->_var['v']; ?>"></script>
    <script type="text/javascript" src="__TPL__/statics/layer/layer.js?v=<?php echo $this->_var['v']; ?>"></script>
    <link rel="stylesheet" href="__TPL__/statics/css/search.css?v=<?php echo $this->_var['v']; ?>" />
    <link rel="stylesheet" href="__TPL__/statics/layer/skin/layer.css?v=<?php echo $this->_var['v']; ?>" />
<script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?02c8bb1ac433b2706baa5c02df3c1c25";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>

    <script type="text/javascript" >var tpl = '__TPL__';</script>
    <?php echo $this->fetch('/library/js_sdk.lbi'); ?>
</head>
<body>