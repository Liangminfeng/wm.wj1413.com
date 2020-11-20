<?php echo $this->fetch('library/page_header.lbi'); ?>
<style type="text/css">
.topic-title img{width:100%;}
</style>

    <?php if ($this->_var['visitor']): ?>

		<div class="s-flex s-top" style="width:100%;"><p><img src="<?php echo empty($this->_var['visitor']['user_avatar']) ? '/themes/yutui/images/web_logo.png' : $this->_var['visitor']['user_avatar']; ?>"></p><span>
		<?php if ($this->_var['visitor']['nick_name']): ?>
		<?php echo $this->_var['visitor']['nick_name']; ?>
		<?php else: ?>
		<?php echo $this->_var['visitor']['user_name']; ?>
		<?php endif; ?><?php echo $this->_var['lang']['xx_jobweb']; ?></span></div>
	<?php endif; ?>
	
<div class="con">
  <?php if ($this->_var['topic']['htmls'] == ''): ?>
  <script language="javascript">
	var img_url      = "<?php echo $this->_var['topic']['topic_img']; ?>";
	
	if (img_url.indexOf('.swf') != -1)
	{
		document.write('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="'+ topic_width +'" height="'+ topic_height +'">');
		document.write('<param name="movie" value="'+ img_url +'"><param name="quality" value="high">');
		document.write('<param name="menu" value="false"><param name=wmode value="opaque">');
		document.write('<embed src="'+ img_url +'" wmode="opaque" menu="false" quality="high" width="'+ topic_width +'" height="'+ topic_height +'" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" wmode="transparent"/>');
		document.write('</object>');
	}
	else
	{
		document.write('<div id="img-photo-box"><img border="0" style="width:100%;" src="' + img_url + '"></div>');
	}
  </script>
  <?php else: ?>
  <?php echo $this->_var['topic']['htmls']; ?>
  <?php endif; ?>
  
  <?php if ($this->_var['topic']['intro'] != ''): ?>
  <div class="topic-title"><?php echo $this->_var['topic']['intro']; ?></div>
  <?php endif; ?>
  
  <?php if ($this->_var['visitor']): ?>

<div style="width: 100%;background: #F2F1F1;text-align: center;color: #47484A;padding: 1rem 0rem;margin-bottom: 1rem;"><?php echo $this->_var['lang']['business_card_msg_1']; ?></div>
<a href="#">
  <div class="my_slfe_card" style="width:94%;text-align: center;margin:auto;position:relative;margin-bottom: 1rem;">
      <img style="width:100%;border: 1px solid #d8d8d8;border-radius: 0.8rem;box-shadow: 0px 0px 18px #ddd;" src="themes/yutui/images/like/Mycard6.png" alt="">
      <div class="some_info">
        <p class="user_names"><?php echo $this->_var['visitor']['nick_name']; ?></p>
        <p class="user_img"><img style="width:100%;border-radius: 100%;" src="<?php echo empty($this->_var['visitor']['user_avatar']) ? '/themes/yutui/images/web_logo.png' : $this->_var['visitor']['user_avatar']; ?>?imageView2/1/w/300/h/300/q/100!|imageslim" alt=""></p>
        <p class="user_jobs"><span style="font-size: 1.5rem;"><?php echo $this->_var['visitor']['company']; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;<span style="font-size: 1.5rem;"><?php echo $this->_var['visitor']['job']; ?></span></p>
        <p class="user_lianxi"><a style="color:#fff;font-size: 1.5rem;" href="<?php echo url('user/business_card');?>&u=<?php echo $this->_var['u']; ?>"><?php echo $this->_var['lang']['business_card_msg_2']; ?></a></p><p class="user_myselfes"><span><?php echo $this->_var['lang']['i_am']; ?><?php echo $this->_var['visitor']['nick_name']; ?>，<?php echo $this->_var['visitor']['sign']; ?></span></p>
        
      </div>
  </div>
</a>
<?php endif; ?>
  
  <?php echo $this->fetch('library/goods_list_topic.lbi'); ?> 
</div>

<?php echo $this->fetch('library/yutui_global_footer.lbi'); ?>
<?php echo $this->fetch('library/new_search.lbi'); ?>
<script type="text/javascript">
if( <?php echo $this->_var['show_asynclist']; ?> == 1){
 	get_asynclist('<?php echo url('topic/asynclist', array('id'=>$this->_var['id'], 'brand'=>$this->_var['brand_id'], 'price_min'=>$this->_var['price_min'], 'price_max'=>$this->_var['price_max'], 'filter_attr'=>$this->_var['filter_attr'], 'page'=>$this->_var['page'], 'sort'=>$this->_var['sort'], 'order'=>$this->_var['order'], 'keywords'=>$this->_var['keywords']));?>' , '__TPL__/images/loader.gif');
 }
</script>

</body></html>