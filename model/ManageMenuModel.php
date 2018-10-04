<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/7/27
 * Time: 下午7:56
 */

namespace manager\model;


class ManageMenuModel extends ManagerModel
{
    protected $menu_table = 'manage_menu';

    protected $priv_menu = [];

    public function init()
    {
        $this->getMenuPriv();
    }

    /**
     * 获取功能权限
     */
    public function getMenuPriv()
    {
        $redis = \NGRedis::$instance->getRedis();
        $privs_menu_key = 'manage_privs_func_'.$this->service->bussine_id;
        $privs_menus = $redis->get($privs_menu_key);
        $privs_menus = ng_mysql_json_safe_decode($privs_menus);

        $white_menu_key = 'manage_white_func';
        $white_menus = $redis->get($white_menu_key);
        $white_menus = ng_mysql_json_safe_decode($white_menus);

        $this->priv_menu = array_merge_recursive($white_menus,$privs_menus);
    }

    /**
     * 检查权限是否允许通过
     * 目前不对插件进行检查
     * @param $req
     * @return bool
     */
    public function assetPrivRight($req)
    {
        $flag = false;
        if($req->request_plugin == 'manager') {
            if ($req->module && $req->action) {
                if(isset($this->priv_menu[$req->request_plugin][$req->module][$req->action])) {
                    $flag = true;
                }
            }
        } else {
            $flag = true;
        }
        return $flag;
    }

    public function getNav()
    {
        $map = [
            'status'=> 1,
            'parentid' => 0
        ];
        $res = $this->db->table($this->menu_table)->orderBy('listorder','desc')->where($map)->get();
        $res = reset($res);
        $lists = [];
        foreach ($res as $key => $val ) {
            //权限检查
            $val = (array)$val;
            $app = $val['app'];
            $model = $val['model'];
            $action = $val['action'];
            if ( $app && $model && $action ) {
                if (isset($this->priv_menu[$app][$model][$action])) {
                    $lists[$key] = (array)$val;
                }
            }
        }
        return $lists;
    }

    public function getSubMenu($parent_id)
    {
        $map = [
            'status'=> 1,
            'parentid' => $parent_id,
        ];
        $res = $this->db->table($this->menu_table)->orderBy('listorder','desc')->where($map)->get();
        $res = reset($res);
        $lists = [];
        foreach ($res as $key => $val ) {
            $res[$key] = $val = (array)$val;
            $app = $val['app'];
            $model = $val['model'];
            $action = $val['action'];
            if ( $app && $model && $action ) {
                if (isset($this->priv_menu[$app][$model][$action])) {
                    $lists[$key] = $val;
                    $subMenus = $this->getSubMenu($res[$key]['id']);
                    $lists[$key]['items'] = $subMenus;
                }
            }
        }
        return $lists;
    }

    public function menuLists($where=[],$order=[],$page=1,$per_page=20,$raw=false)
    {
        return $this->tableLists($this->menu_table,$where,$order,$page,$per_page,$raw);
    }

    public function menuCount($where=[],$raw=false)
    {
        return $this->tableCount($this->menu_table,$where,$raw);
    }

    public function menuInfo($where=[])
    {
        $res = $this->db->table($this->menu_table)->where($where)->first();
        if ($res) {
            $res = (array) $res;
        }
        return $res;
    }

    public function addMenu($map)
    {
        $flag = $this->db->table($this->menu_table)->insertGetId($map);
        return $flag;
    }

    public function saveMenu($where=[],$map)
    {
        $flag = $this->db->table($this->menu_table)->where($where)->update($map);
        return $flag;
    }

    public function deleteMenu($where,$raw=false)
    {
        $obj = $this->db->table($this->menu_table);
        if (!$raw) {
            $obj=$obj->where($where);
        } else {
            $obj=$obj->whereRaw($where[0],$where[1]);
        }
        return $obj->delete();
    }

}