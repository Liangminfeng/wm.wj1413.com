<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ArticleBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 文章基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class PosterBaseModel extends BaseModel {

    protected $table = 'poster';

    /**
     * 获得文章分类下的文章列表
     * 
     * @access public
     * @param integer $cat_id            
     * @param integer $page            
     * @param integer $size            
     * @return array
     */
    public function get_cat_poster($cat_id, $page = 1, $size = 20, $requirement = '',$type = 'default',$resourcetype = false) {
        // 取出所有非0的文章
        if ($cat_id == '-1') {
            $cat_str = 'cat_id > 0';
        } else {
            $cat_str = 'cat_id = '.$cat_id;
           // $cat_str = get_article_children($cat_id);
        }
   
        $condition = 'a.is_open = 1 AND ' . "a.". $cat_str;
        // 增加搜索条件，如果有搜索内容就进行搜索
        if ($requirement != '') {
            $condition .= ' AND title like \'%' . $requirement . '%\'';
        }
       
        $limit = ($page - 1) * $size . ',' . $size;

        $resourceWhere="";
        if($resourcetype) $resourceWhere = " AND ar.resource_type = ".$resourcetype;
        
        if ($type =="click") {
             
             $sql = "select * from ".$this->pre."poster as a inner join ".$this->pre."poster_resource as ar on  a.cat_id = ar.cat_id where ".$condition." ".$resourceWhere." order by click_count DESC limit ".$limit;
           

            // $sql = "select article_id, title,cat_id, author, add_time, file_url, open_type,link,click_count from ".$this->pre."article where  ".$condition." order by click_count DESC limit ".$limit;
        }else{

            $sql = "select * from ".$this->pre."poster as a inner join ".$this->pre."poster_resource as ar on  a.cat_id = ar.cat_id where ".$condition." ".$resourceWhere." order by a.sort ASC,a.poster_id DESC limit ".$limit;
            
      
         
        }
  
        
     
        $list = $this->query($sql);
            
        $i = 1;
        $arr = array();
        if (is_array($list)) {
            foreach ($list as $vo) {

                $poster_id = $vo['poster_id'];
                $arr[$poster_id]['id'] = $poster_id;
                $arr[$poster_id]['index'] = $i;
                $arr[$poster_id]['title'] = $vo['title'];
                $arr[$poster_id]['target_title'] = $vo['target_title'];
                $arr[$poster_id]['target'] = $vo['target'];
                $arr[$poster_id]['short_title'] = C('article_title_length') > 0 ? sub_str($vo['title'], C('article_title_length')) : $vo['title'];
                $arr[$poster_id]['author'] = empty($vo['author']) || $vo['author'] == '_SHOPHELP' ? C('shop_name') : $vo['author'];
                $arr[$poster_id]['url'] = $vo['link'] && $vo['link'] !='http://' ?  $vo['link'] : url('poster/info', array('aid' => $poster_id)) ;
                $arr[$poster_id]['add_time'] = date(C('date_format'), $vo['add_time']);
                $arr[$poster_id]['file_url'] = $vo['file_url'];
                $arr[$poster_id]['cat_id'] = $vo['cat_id'];
                $arr[$poster_id]['click_count'] = $vo['click_count'];
                $i++;
            }
        }
        return $arr;
    }

    /**
     * 获得指定分类下的海报总数
     * 
     * @param integer $cat_id            
     * @return integer
     */
    public function get_poster_count($cat_id, $requirement = '') {
        //$condition = get_poster_children($cat_id) . ' AND is_open = 1';
        $condition = ' is_open = 1'.' AND cat_id = '.$cat_id;
        if ($requirement != '') {
            $condition .= ' AND title like \'%' . $requirement . '%\'';
        }

        return $this->count($condition);
    }

    /**
     * 获得指定文章分类下所有底层分类的ID
     *
     * @access public
     * @param integer $cat
     *            指定的分类ID
     *            
     * @return void
     */
    function get_article_children($cat = 0) {
        return db_create_in(array_unique(array_merge(array(
            $cat
                                ), array_keys($this->article_cat_list($cat, 0, false)))), 'cat_id');
    }

    /**
     * 获得指定分类下的子分类的数组
     *
     * @access public
     * @param int $cat_id
     *            分类的ID
     * @param int $selected
     *            当前选中分类的ID
     * @param boolean $re_type
     *            返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param int $level
     *            限定返回的级数。为0时返回所有级数
     * @return mix
     */
    function poster_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0,$resourcetype =false) {
        
        static $res = NULL;
        if ($res === NULL) {
            $data = read_static_cache('poster_cat_pid_releate');
            if ($data === false) {
                $sql = "SELECT s.*, COUNT(s.cat_id) AS has_children, COUNT(a.poster_id) AS poster_num " . ' FROM ' . 
                $this->pre . "poster_cat AS s" . " LEFT JOIN " . $this->pre . "poster AS a ON a.cat_id=s.cat_id" . " GROUP BY s.cat_id " . " ORDER BY parent_id, sort_order ASC";
            
                $res = $this->query($sql);
                write_static_cache('poster_cat_pid_releate', $res);
            } else {
                $res = $data;
            }
        }

        if (empty($res) == true) {
            return $re_type ? '' : array();
        }
       
        //var_dump($res);exit;
        $options = $this->poster_cat_options($cat_id, $res,$resourcetype); // 获得指定分类下的子分类的数组
     
        /* 截取到指定的缩减级别 */
        if ($level > 0) {
            if ($cat_id == 0) {
                $end_level = $level;
            } else {
                $first_item = reset($options); // 获取第一个元素
                $end_level = $first_item['level'] + $level;
            }

            /* 保留level小于end_level的部分 */
            foreach ($options as $key => $val) {
                if ($val['level'] >= $end_level) {
                    unset($options[$key]);
                }
            }
        }

        $pre_key = 0;
        foreach ($options as $key => $value) {
            $options[$key]['has_children'] = 1;
            if ($pre_key > 0) {
                if ($options[$pre_key]['cat_id'] == $options[$key]['parent_id']) {
                    $options[$pre_key]['has_children'] = 1;
                }
            }
            $pre_key = $key;
        }

        if ($re_type == true) {
            $select = '';    
            foreach ($options as $var) {
                $select .= '<option value="' . $var['cat_id'] . '" ';
                $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
                $select .= '>';
                if ($var['level'] > 0) {
                    $select .= str_repeat('&nbsp;', $var['level'] * 4);
                }
                $select .= htmlspecialchars(addslashes($var['cat_name'])) . '</option>';
            }

            return $select;
        } else {
            foreach ($options as $key => $value) {
                $options[$key]['url'] = url('poster/index', array(
                    'id' => $value['cat_id']
                ));
            }
            return $options;
        }
    }

    /**
     * 过滤和排序所有文章分类，返回一个带有缩进级别的数组
     *
     * @access private
     * @param int $cat_id
     *            上级分类ID
     * @param array $arr
     *            含有所有分类的数组
     * @param int $level
     *            级别
     * @return void
     */
    function poster_cat_options($spec_cat_id, $arr,$resourcetype=false) {

        static $cat_options = array();

        if (isset($cat_options[$spec_cat_id])) {
            return $cat_options[$spec_cat_id];
        }

        if (!isset($cat_options[0])) {
            $level = $last_cat_id = 0;
            $options = $cat_id_array = $level_array = array();
            while (!empty($arr)) {
                foreach ($arr as $key => $value) {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0) {
                        if ($value['parent_id'] > 0) {
                            break;
                        }

                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0) {
                            continue;
                        }
                        $last_cat_id = $cat_id;
                        $cat_id_array = array(
                            $cat_id
                        );
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id) {
                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0) {
                            if (end($cat_id_array) != $last_cat_id) {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    } elseif ($value['parent_id'] > $last_cat_id) {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1) {
                    $last_cat_id = array_pop($cat_id_array);
                } elseif ($count == 1) {
                    if ($last_cat_id != end($cat_id_array)) {
                        $last_cat_id = end($cat_id_array);
                    } else {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id])) {
                    $level = $level_array[$last_cat_id];
                } else {
                    $level = 0;
                }
            }
            $cat_options[0] = $options;
        } else {
            $options = $cat_options[0];
        }

        if (!$spec_cat_id) {
            return $options;
        } else {
            if (empty($options[$spec_cat_id])) {
                return array();
            }

            $spec_cat_id_level = $options[$spec_cat_id]['level'];

            foreach ($options as $key => $value) {

                if ($key != $spec_cat_id) {
                    unset($options[$key]);
                } else {
                    break;
                }
            }

            $spec_cat_id_array = array();
            foreach ($options as $key => $value) {
                if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) || ($spec_cat_id_level > $value['level'])) {
                    break;
                } else {
                    $spec_cat_id_array[$key] = $value;
                }
            }
            $cat_options[$spec_cat_id] = $spec_cat_id_array;

            return $spec_cat_id_array;
        }
    }

    /* 获得海报列表 */

    function get_posterslist($offset) {
      
        $result = get_filter();
        if ($result === false) {
            $filter = array();
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'a.article_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $where = '';
            if (!empty($filter['keyword'])) {
                $where = " AND a.title LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
            }
            if ($filter['cat_id']) {
                $where .= " AND a." . get_article_children($filter['cat_id']);
            }

            /* 文章总数 */
            $sql = 'SELECT COUNT(*)as count FROM ' . $this->pre . 'article AS a ' .
                    'LEFT JOIN ' . $this->pre . 'article_cat AS ac ON ac.cat_id = a.cat_id ' .
                    'WHERE 1 ' . $where;
            $res = $this->row($sql);
            
            $filter['record_count'] = $res['count'];

            $filter = page_and_size($filter);

            /* 获取文章数据 */
            $sql = 'SELECT p.* , pc.cat_name ' .
                    'FROM ' . $this->pre . 'poster AS p ' .
                    'LEFT JOIN ' . $this->pre . 'poster_cat AS pc ON pc.cat_id = p.cat_id ' .
                    'WHERE 1 ' . $where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'] .' LIMIT '.$offset;

            $filter['keyword'] = stripslashes($filter['keyword']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $arr = array();
        $res  = $this->query($sql);
        foreach( $res as $rows){
            $rows['date'] = local_date(C('time_format'), $rows['add_time']);
            $arr[] = $rows;
        }    
        return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    }

}
