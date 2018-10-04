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
        $redis_key = 'manage_privs_limit_'.$company_id;
        $redis = \NGRedis::$instance->getRedis();
        $config = $redis->get($redis_key);
        if($config) {
            $config = ng_mysql_json_safe_decode($config);
            return $config;
        } else {
            throw new \Exception('没有权限创建');
        }
    }

    /**
     * 判断是否最大的子账号限制
     * @param $company_id
     * @param $current_num
     * @throws \Exception
     */
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

    /**
     * 判断是否最大的业务量限制
     * @param $company_id
     * @param $current_num
     * @throws \Exception
     */
    public function assert_max_work_limit($company_id,$current_num)
    {
        $config = $this->get_limit_config($company_id);
        if (isset($config['max_work_count']) && $config['max_work_count']!=0) {
            if ($config['max_work_count'] <= $current_num) {
                throw new \Exception('已经到达最大限制');
            }
        } else {
            throw new \Exception('没有权限创建');
        }
    }




}