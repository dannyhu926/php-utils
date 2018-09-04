<?php
/**
 * Tree.php  通用的树型类，可以生成任何树型结构
 *
 * @author   hudy <469671292@163.com>
 */
namespace Utils;


class Tree
{
    public $param_id = 'id';
    public $param_parent_id = 'parent_id';
    public $param_name = 'name';
    public $param_children = 'children';

    /**
     * 生成树型结构所需要的2维数组
     * @var array
     */
    protected $arr = array();

    /**
     * 生成树型结构所需修饰符号，可以换成图片
     * @var array
     */
    public $icon = array('│', '├', '└');

    protected $nbsp = "&nbsp;";

    protected $ret = '';

    protected $str = '';


    /**
     * 构造函数，初始化类
     * @param array 2维数组，例如：
     * array(
     *      1 => array('id'=>'1','parentid'=>0,'name'=>'一级栏目一'),
     *      2 => array('id'=>'2','parentid'=>0,'name'=>'一级栏目二'),
     *      3 => array('id'=>'3','parentid'=>1,'name'=>'二级栏目一'),
     *      4 => array('id'=>'4','parentid'=>1,'name'=>'二级栏目二'),
     *      5 => array('id'=>'5','parentid'=>2,'name'=>'二级栏目三'),
     *      6 => array('id'=>'6','parentid'=>3,'name'=>'三级栏目一'),
     *      7 => array('id'=>'7','parentid'=>3,'name'=>'三级栏目二')
     *      )
     */
    public function __construct($arr = array(), $params_alias = array()) {
        $this->arr = $arr;
        $this->ret = '';

        $params_alias['id'] && $this->param_id = $params_alias['id'];
        $params_alias['parent_id'] && $this->param_parent_id = $params_alias['parent_id'];
        $params_alias['name'] && $this->param_name = $params_alias['name'];
        $params_alias['children'] && $this->param_children = $params_alias['children'];
        return is_array($arr);
    }

    /**
     * 得到父级数组
     * @param int
     * @return array
     */
    public function getParent($myid) {
        $newarr = array();
        if (!isset ($this->arr[$myid]))
            return false;
        $pid = $this->arr[$myid][$this->param_parent_id];
        $pid = $this->arr[$pid][$this->param_parent_id];
        if (is_array($this->arr)) {
            foreach ($this->arr as $id => $a) {
                if ($a[$this->param_parent_id] == $pid)
                    $newarr[$id] = $a;
            }
        }
        return $newarr;
    }

    /**
     * 得到子级数组
     * @param int
     * @return array
     */
    public function getChild($myid) {
        $a = $newarr = array();
        if (is_array($this->arr)) {
            foreach ($this->arr as $id => $a) {
                if ($a[$this->param_parent_id] == $myid)
                    $newarr[$id] = $a;
            }
        }
        return $newarr ? $newarr : false;
    }

    /**
     * 递归无限级分类【先序遍历算】，获取任意节点下所有子孩子
     * @param int $myid 父级节点
     * @param int $level 层级数
     * @return array $arrTree 排序后的数组
     */
    public function getChilds($myid = 0, $level = 0) {
        static $arrTree = [];
        if (empty($this->arr)) return false;
        $level++;
        foreach ($this->arr as $key => $value) {
            if ($value[$this->param_parent_id] == $myid) {
                $value['level'] = $level;
                $arrTree[$value[$this->param_id]] = $value;
                unset($this->arr[$key]); //注销当前节点数据，减少已无用的遍历
                $this->getChilds($value[$this->param_id], $level);
            }
        }

        return $arrTree;
    }

    /**
     * 生成树状结构
     *
     * @param int $myid
     *
     * @return array
     */
    public function makeTree($myid = 0) {
        $childs = $this->getChild($myid);
        if (empty($childs)) {
            return $childs;
        }
        foreach ($childs as $k => &$v) {
            $child = $this->makeTree($v[$this->param_id]);
            if (!empty($child)) {
                sort($child);
                $v['children'] = $child;
            }
        }
        unset($v);

        return $childs;
    }

    /**
     * 得到当前位置数组
     * @param int
     * @return array
     */
    public function getPos($myid, & $newarr) {
        $a = array();
        if (!isset ($this->arr[$myid]))
            return false;
        $newarr[] = $this->arr[$myid];
        $pid = $this->arr[$myid][$this->param_parent_id];
        if (isset ($this->arr[$pid])) {
            $this->getPos($pid, $newarr);
        }
        if (is_array($newarr)) {
            krsort($newarr);
            foreach ($newarr as $v) {
                $a[$v[$this->param_id]] = $v;
            }
        }
        return $a;
    }

    /**
     * 得到树型结构
     * @param int $myid 表示获得这个ID下的所有子级
     * @param string $str 生成树型结构的基本代码，例如："<option value=\$id \$selected>\$spacer\$name</option>"
     * @param int $sid 被选中的ID，比如在做树型下拉框的时候需要用到
     * @return string
     */
    public function getTree($myid, $str, $sid = 0, $adds = '', $str_group = '', $level = -1) {
        $number = 1;
        if ($level == 0) {
            return $this->ret;
        }
        if ($level) {
            $level--;
        }
        $child = $this->getChild($myid);
        if (is_array($child)) {
            $total = count($child);
            $nstr = "";
            $parent_id = "";
            foreach ($child as $id => $a) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer = $adds ? $adds . $j : '';
                $selected = $a[$this->param_id] == $sid || (is_array($sid) && in_array($a[$this->param_id], $sid)) ? 'selected' : '';
                @extract($a); // 对于数组中的每个元素，键名用于变量名，键值用于变量值
                $parent_id == 0 && $str_group ? eval ("\$nstr = \"$str_group\";") : eval ("\$nstr = \"$str\";");
                $this->ret .= $nstr;
                $nbsp = $this->nbsp;
                $this->getTree($id, $str, $sid, $adds . $k . $nbsp, $str_group, $level);
                $number++;
            }
        }
        return $this->ret;
    }

    /**
     * 同上一方法类似,但允许多选
     */
    public function getTreeMulti($myid, $str, $sid = 0, $adds = '') {
        $number = 1;
        $child = $this->getChild($myid);
        if (is_array($child)) {
            $total = count($child);
            $nstr = "";
            foreach ($child as $id => $a) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer = $adds ? $adds . $j : '';

                $selected = $this->have($sid, $id) ? 'selected' : '';
                @ extract($a);
                eval ("\$nstr = \"$str\";");
                $this->ret .= $nstr;
                $this->getTreeMulti($id, $str, $sid, $adds . $k . '&nbsp;');
                $number++;
            }
        }
        return $this->ret;
    }

    /**
     * @param integer $myid 要查询的ID
     * @param string $str 第一种HTML代码方式
     * @param string $str2 第二种HTML代码方式
     * @param integer $sid 默认选中
     * @param integer $adds 前缀
     */
    public function getTreeCategory($myid, $str, $str2, $sid = 0, $adds = '') {
        $number = 1;
        $child = $this->getChild($myid);
        if (is_array($child)) {
            $total = count($child);
            $nstr = "";
            foreach ($child as $id => $a) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer = $adds ? $adds . $j : '';

                $selected = $this->have($sid, $id) ? 'selected' : '';
                @extract($a);
                if (empty ($html_disabled)) {
                    eval ("\$nstr = \"$str\";");
                } else {
                    eval ("\$nstr = \"$str2\";");
                }
                $this->ret .= $nstr;
                $this->getTreeCategory($id, $str, $str2, $sid, $adds . $k . '&nbsp;');
                $number++;
            }
        }
        return $this->ret;
    }

    /**
     * 同上一类方法，jquery treeview 风格，可伸缩样式（需要treeview插件支持）
     * @param $myid 表示获得这个ID下的所有子级
     * @param $effected_id 需要生成treeview目录数的id
     * @param $str 末级样式
     * @param $str2 目录级别样式
     * @param $showlevel 直接显示层级数，其余为异步显示，0为全部限制
     * @param $style 目录样式 默认 filetree 可增加其他样式如'filetree treeview-famfamfam'
     * @param $currentlevel 计算当前层级，递归使用 适用改函数时不需要用该参数
     * @param $recursion 递归使用 外部调用时为FALSE
     */
    public function getTreeView($myid, $effected_id = 'example', $str = "<span class='file'>\$name</span>", $str2 = "<span class='folder'>\$name</span>", $showlevel = 0, $style = 'filetree ', $currentlevel = 1, $recursion = FALSE) {
        $child = $this->getChild($myid);
        if (!defined('EFFECTED_INIT')) {
            $effected = ' id="' . $effected_id . '"';
            define('EFFECTED_INIT', 1);
        } else {
            $effected = '';
        }
        $placeholder = '<ul><li><span class="placeholder"></span></li></ul>';
        if (!$recursion) $this->str .= '<ul' . $effected . '  class="' . $style . '">';
        $nstr = "";
        foreach ($child as $id => $a) {
            @extract($a);
            if ($showlevel > 0 && $showlevel == $currentlevel && $this->get_child($id)) $folder = 'hasChildren'; //如设置显示层级模式@2011.07.01
            $floder_status = isset($folder) ? ' class="' . $folder . '"' : '';
            $this->str .= $recursion ? '<ul><li' . $floder_status . ' id=\'' . $id . '\'>' : '<li' . $floder_status . ' id=\'' . $id . '\'>';
            $recursion = FALSE;
            if ($this->getChild($id)) {
                eval("\$nstr = \"$str2\";");
                $this->str .= $nstr;
                if ($showlevel == 0 || ($showlevel > 0 && $showlevel > $currentlevel)) {
                    $this->getTreeView($id, $effected_id, $str, $str2, $showlevel, $style, $currentlevel + 1, TRUE);
                } elseif ($showlevel > 0 && $showlevel == $currentlevel) {
                    $this->str .= $placeholder;
                }
            } else {
                eval("\$nstr = \"$str\";");
                $this->str .= $nstr;
            }
            $this->str .= $recursion ? '</li></ul>' : '</li>';
        }
        if (!$recursion) $this->str .= '</ul>';
        return $this->str;
    }

    private function have($list, $item) {
        return (strpos(',,' . $list . ',', ',' . $item . ','));
    }

    /**
     * 输出树形结构
     */

    /**
     * 获取子栏目json
     * Enter description here ...
     * @param unknown_type $myid
     */
    public function createSubJson($myid, $str = '') {
        $sub_cats = $this->getChild($myid);
        $n = 0;
        if (is_array($sub_cats)) foreach ($sub_cats as $c) {
            $data[$n]['id'] = $c[$this->param_id];
            if ($this->getChild($c[$this->param_id])) {
                $data[$n]['liclass'] = 'hasChildren';
                $data[$n][$this->param_children] = array(array('text' => '&nbsp;', 'classes' => 'placeholder'));
                $data[$n]['classes'] = 'folder';
                $data[$n]['text'] = $c[$this->param_name];
            } else {
                if ($str) {
                    @extract($c);
                    eval("\$data[$n]['text'] = \"$str\";");
                } else {
                    $data[$n]['text'] = $c[$this->param_name];
                }
            }
            $n++;
        }
        return json_encode($data);
    }

    public function getArray($myid = 0, $adds = '') {
        $number = 1;
        $child = $this->getChild($myid);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $a) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer = $adds ? $adds . $j : '';
                @extract($a);
                $a[$this->param_name] = $spacer . $a[$this->param_name];
                $this->ret[$a[$this->param_id]] = $a;
                $fd = $adds . $k . $this->nbsp;
                $this->getArray($id, $fd);
                $number++;
            }
        }

        return $this->ret;
    }
}