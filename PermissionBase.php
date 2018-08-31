<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/7/28
 * Time: 上午1:20
 */

namespace manager;

use libs\asyncme\Plugins as Plugins;
use libs\asyncme\RequestHelper as RequestHelper;
use libs\asyncme\ResponeHelper as ResponeHelper;
use \Slim\Http\UploadedFile;

class PermissionBase extends ManagerBase
{
    public $sessions;

    public function auth()
    {
        $req = $this->service->getRequestHelper();
        $bid = $req->company_id;

        $path = [
            'mark' => 'manager',
            'bid'  => $bid,
            'pl_name'=>'manager',
        ];
        $query = [
            'mod'=>'pub',
            'act'=>'login'
        ];

        $url = urlGen($req,$path,$query);
        $time = 0;
        $mess = '';

        $session = $this->service->getSession();

        $sessions['manager_uid'] = $session->get('manager_uid');
        $sessions['manager_user'] = $session->get('manager_user');
        $sessions['manager_name'] = $session->get('manager_name');
        $sessions['manager_avatar'] = $session->get('manager_avatar');
        $sessions['manager_login_time'] = $session->get('manager_login_time');

        $this->sessions = $sessions;

        $status = $session->get('manager_user') ? true : false;
        return [
            'status'=>$status,
            'mess'=>$mess,
            'url'=>$url,
            'time'=>$time,
        ];

    }
}