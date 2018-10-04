<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/7/24
 * Time: 下午7:09
 */

namespace manager;

use manager\model;
use libs\asyncme\RequestHelper;

class Index extends PermissionBase
{
    /**
     * @param RequestHelper $req
     * @param array $preData
     * @return mixed
     * @priv allow
     */
    public function infoAction(RequestHelper $req,array $preData)
    {
        $plugin_req = $req;
        $plugin_req->request_plugin = 'moon_shot';
        $plugin_req->action = 'index';

        $plugin_reponse = ng_plugins($plugin_req,$this->service);
        return $plugin_reponse;
    }

    /**
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     * @priv allow
     */
    public function tAction(RequestHelper $req,array $preData)
    {
        $status = true;
        $mess = '成功';
        $data = [
            'test'=>'hello admin!',
            'req'=>$req,
        ];

        return $this->render($status,$mess,$data);
    }

    /**
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     * @priv allow
     */
    public function indexAction(RequestHelper $req,array $preData)
    {
        $status = true;
        $mess = '成功';

        $nav_data = $this->nav_default($req,$preData);


        //ng_func_privilege_check($req->company_id,$this->sessions['admin_uid'],'index');

        $data = [
            'title'=>'hello manager!',
            'content'=>'',
            'bid'=>$req->company_id,
        ];
        $data = array_merge($nav_data,$data);
        return $this->render($status,$mess,$data,'template','Index/index');
    }

    /**
     * @param RequestHelper $req
     * @param array $preData
     * @return mixed
     * @priv allow
     */
    public function codeAction(RequestHelper $req,array $preData)
    {
        $plugin_req = $req;
        $plugin_req->request_plugin = 'verification_code';
        $plugin_req->action = 'gen';

        $plugin_reponse = ng_plugins($plugin_req,$this->service);
        return $plugin_reponse;
    }

    /**
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     * @priv allow
     */
    public function urlAction(RequestHelper $req,array $preData)
    {

        $path = [
            'mark' => 'plugin',
            'bid'  => $req->company_id,
            'pl_name'=>'verification_code',
        ];
        $query = [
            'act'=>'gen'
        ];
        $url = urlGen($req,$path,$query,true);

        $status = true;
        $mess = '成功';
        $data = [
            'url' => $url,
        ];
        return $this->render($status,$mess,$data);
    }

    /**
     * @name 仪表盘
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     * @priv ask
     */
    public function dashboardAction(RequestHelper $req,array $preData)
    {
        $status = true;
        $mess = '成功';
        $data = [

        ];
        return $this->render($status,$mess,$data,'template','empty');
    }
}