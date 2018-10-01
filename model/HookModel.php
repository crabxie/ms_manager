<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/8/2
 * Time: 下午1:55
 */

namespace manager\model;


class HookModel extends ManagerModel
{
    protected $manage_num_limit_table = 'manage_num_limit';


    protected function get_limit_config($company_id)
    {
        $map = [
            'company_id'=>$company_id,
        ];
        $res = $this->db->table($this->manage_num_limit_table)->where($map)->first();
        $res = (array)$res;
        if($res && $res['config']) {
            $config = ng_mysql_json_safe_decode($res['config']);
            return $config;
        } else {
            throw new \Exception('没有权限创建');
        }
    }

    public function assert_max_sub_limit($company_id,$current_num)
    {
        $config = $this->get_limit_config($company_id);
        if (isset($config['max_sub_account']) && $config['max_sub_account']!=0) {
            if ($config['max_sub_account'] <= $current_num) {
                throw new \Exception('已经到达最大限制');
            }
        } else {
            throw new \Exception('没有权限创建');
        }
    }





}