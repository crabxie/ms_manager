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
        $work_model = new model\WorkModel($this->service);
        $frontend_model = new model\FontendUserModel($this->service);
        $material_model = new model\AssetsModel($this->service);
        $hook_model = new  model\HookModel($this->service);
        $works_app_model = new model\WorksAppModel($this->service);

        $where = [
            'company_id'=>$req->company_id,
        ];
        $total_work_count = $work_model->worksCount($where);
        $total_work_app_count = $works_app_model->worksAppCount($where);
        $frontend_user_count = $frontend_model->userCount($where);
        $material_count = $material_model->assetsCount($where);
        $max_work_count = $hook_model->get_max_work_limit($where['company_id']);


        $byme_where  = array_merge($where,['account_id'=>$this->sessions['manager_uid']]);
        $total_work_byme_count = $work_model->worksCount($byme_where);

        $last_24hour_time = strtotime('-1 day',time());

        $today_where = [];
        $today_where[0] = "company_id = ?  and ctime > ? ";
        $today_where[1] = [$req->company_id, $last_24hour_time];
        $frontend_user_today_count = $frontend_model->userCount($today_where,true);
        $material_today_count = $material_model->assetsCount($today_where,true);

        $lists = [
            '业务'=>[
                ['name'=>'业务总数','value'=>$total_work_count],
                ['name'=>'我创建的业务','value'=>$total_work_byme_count],
                ['name'=>'允许创建业务数','value'=>$max_work_count],
            ],
            '应用'=>[
                ['name'=>'应用总数','value'=>$total_work_app_count],
            ],
            '用户'=>[
                ['name'=>'用户总数','value'=>$frontend_user_count],
                ['name'=>'进入新增用户数','value'=>$frontend_user_today_count],
            ],
            '素材'=>[
                ['name'=>'素材总数','value'=>$material_count],
                ['name'=>'今日新增素材数','value'=>$material_today_count],
            ],
            '插件'=>[
                ['name'=>'插件总数','value'=>0],
            ],
            'PV/UV'=>[
                ['name'=>'总PV','value'=>0],
                ['name'=>'总UV','value'=>0],
                ['name'=>'今日PV','value'=>0],
                ['name'=>'今日UV','value'=>0],
            ],
        ];


        $data = [
            'lists'=>$lists,

        ];
        return $this->render($status,$mess,$data,'template','Index/dashboard');
    }
}