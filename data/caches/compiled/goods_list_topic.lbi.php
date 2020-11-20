<style>
.clearfix{height: 3em;line-height: 3em; background-color: #fff; text-align:center;}
.clearfix h3{line-height:2em; margin-left:10px;}
</style>
<?php $_from = $this->_var['sort_goods_arr']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('sort_name', 'sort');if (count($_from)):
    foreach ($_from AS $this->_var['sort_name'] => $this->_var['sort']):
?>
<div class="clearfix" style="display:none">
  <h3><span><?php echo $this->_var['sort_name']; ?></span></h3>
</div>
<div class="ect-margin-tb ect-pro-list ect-margin-bottom0 ect-border-bottom0">
  <ul id="J_ItemList">
    <?php $_from = $this->_var['sort_goods_arr']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('sort_name', 'sort');if (count($_from)):
    foreach ($_from AS $this->_var['sort_name'] => $this->_var['sort']):
?>
  <?php $_from = $this->_var['sort']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'goods');if (count($_from)):
    foreach ($_from AS $this->_var['goods']):
?>
    <li class="single_item" onclick="window.open('<?php echo $this->_var['goods']['url']; ?>')"> <a href="<?php echo $this->_var['goods']['url']; ?>" style="height:7em;width:7em;"><img src="<?php echo $this->_var['goods']['goods_thumb']; ?>" alt="<?php echo $this->_var['goods']['name']; ?>"></a>
      <dl class="s-flex s-wrap s-justify-a" style="height: 7em; align-content: center;flex-direction: column;">
        <dt style="width: 100%">
          <h4 class="title" style="padding-right: 0px;"><a href="<?php echo $this->_var['goods']['url']; ?>"><?php echo $this->_var['goods']['goods_name']; ?></a></h4>
        </dt>
        <dd class="n-goods-top" style="margin-top: 1rem;"><span class="pull-left"><em><b class="ect-colory"><?php if ($this->_var['goods']['promote_price']): ?><?php echo $this->_var['goods']['promote_price']; ?><?php else: ?><?php echo $this->_var['goods']['shop_price']; ?><?php endif; ?></b></em><small class="ect-margin-lr"><del><?php echo $this->_var['goods']['market_price']; ?></del></small></span><span class="ect-pro-price"> 
          <?php if ($this->_var['goods']['promotion']): ?> 
          <?php $_from = $this->_var['goods']['promotion']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'promotion');if (count($_from)):
    foreach ($_from AS $this->_var['promotion']):
?> 
          <?php if ($this->_var['promotion']['type'] == 'group_buy'): ?><i class="label tuan"><?php echo $this->_var['lang']['group_buy_act']; ?></i> 
          <?php elseif ($this->_var['promotion']['act_type'] == 0): ?> <i class="label mz"> <?php echo $this->_var['lang']['favourable_mz']; ?></i> 
          <?php elseif ($this->_var['promotion']['act_type'] == 1): ?> <i class="label mj"> <?php echo $this->_var['lang']['favourable_mj']; ?></i> 
          <?php elseif ($this->_var['promotion']['act_type'] == 2): ?> <i class="label zk"> <?php echo $this->_var['lang']['favourable_zk']; ?></i> 
          <?php endif; ?> 
          <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> 
          <?php endif; ?> 
          </span></dd>
        <dd style="display:none;"><span class="pull-left <?php if ($this->_var['goods']['mysc'] != 0): ?>ect-colory<?php endif; ?>"><i class="fa <?php if ($this->_var['goods']['mysc'] != 0): ?>fa-heart<?php else: ?>fa-heart-o<?php endif; ?>"></i> <?php echo $this->_var['goods']['sc']; ?><?php echo $this->_var['lang']['like_num']; ?></span><span class="pull-right"><?php echo $this->_var['lang']['sort_sales']; ?>：<?php echo $this->_var['goods']['sales_count']; ?><?php echo $this->_var['lang']['piece']; ?></span> </dd>
      </dl>
    </li>
    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
  </ul>
</div>
<?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> 