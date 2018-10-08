<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/9/13
 * Time: 下午4:29
 */

namespace manager;

use libs\asyncme\RequestHelper;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\utils\PHPSQLParserConstants;
use libs\asyncme\Page as Page;

class Work extends PermissionBase
{

    /**
     * @name 首页
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function indexAction(RequestHelper $req, array $preData)
    {
        $status = true;
        $mess = '成功';

        $nav_data = $this->nav_default($req, $preData);

        $path = [
            'mark' => 'manager',
            'bid' => $req->company_id,
            'pl_name' => 'manager',
        ];
        $query = [
            'mod' => 'work',
            'act' => 'work'
        ];
        $default_frame_url = urlGen($req, $path, $query, true);


        $data = [
            'default_frame_name' => '业务管理',
            'default_frame_url' => $default_frame_url,
        ];
        $data = array_merge($nav_data, $data);

        return $this->render($status, $mess, $data, 'template', 'work/index');
    }

    /**
     * @name 业务列表
     * @priv ask
     */
    public function workAction(RequestHelper $req, array $preData)
    {
        try{
            $nav_data = $this->nav_default($req,$preData);
            $where =[];
            $raw = false;

            if ($req->request_method == 'POST') {
                $formget = $req->post_datas['formget'];
            } else {
                $keyword = urldecode($req->query_datas['keyword']);
                $formget['keyword'] = $keyword;
            }
            $formget['company_id'] = $req->company_id;

            if ($formget) {
                if ($formget['company_id'] && !$formget['keyword']) {
                    $raw = false;
                    $where['company_id']=$formget['company_id'];
                } else if  (isset($formget['group_id']) && $formget['keyword']) {
                    $where[0] = "company_id = ?  and name = ? ";
                    $where[1] = [$formget['company_id'], '%'.$formget['keyword'].'%'];
                    $raw = true;
                }
            }

            $assets_model = new model\W ($this->service);
            $total = $assets_model->assetsCount($where,$raw);

        } catch(\Exception $e) {
            $error = $e->getMessage();
        }
        if ($error) {
            $status = false;
            $mess = $error;
            $data = [];
        } else {
            $status = true;
            $mess = '成功';
        }
    }

    /**
     * @name 添加业务
     * @priv ask
     */
    public function work_addAction()
    {

    }
    /**
     * @name 编辑业务
     * @priv ask
     */
    public function work_editAction()
    {

    }

    /**
     * @name 子编辑
     * @priv ask
     */
    public function work_subeditAction()
    {

    }
    /**
     * @name 通用
     * @priv allow
     */
    public function work_common()
    {

    }

    /**
     * @name 分类设置
     * @priv allow
     */
    protected function work_cates($cate='')
    {
        if(!is_numeric($cate)) $cate='**';
        $cates = [
            0=>'默认分类',
            1=>'小程序',
            2=>'网站',
        ];

        if('**'==$cate) {
            return $cates;
        } else {
            return $cates[$cate-1];
        }
    }
}