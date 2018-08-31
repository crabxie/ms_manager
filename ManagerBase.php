<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/7/26
 * Time: 下午6:03
 */

namespace manager;

use libs\asyncme\Plugins as Plugins;
use libs\asyncme\RequestHelper as RequestHelper;
use libs\asyncme\ResponeHelper as ResponeHelper;
use \Slim\Http\UploadedFile;
use libs\asyncme\Page as Page;

include_once NG_ROOT.'/manager/utils/common_func.php';

class ManagerBase extends Plugins
{

    public  $global_view_var = [];

    //初始化代码 ，自己调用  看在第几层的情况下调用
    // 0 母程序初始化
    // 1 初始化数据库后
    // 2 初始化模版对象后
    public function initialize($level=0)
    {
        if($level==2){
            $cache_key = 'manager_global_view_val';
            $this->global_view_var = $this->service->getCache()->get($cache_key);

            if (!$this->global_view_var) {
                $model = new model\ConfigModel($this->service);
                $config_vals = $model->getConfigInfo(['name'=>'manager_global']);
                if ($config_vals && isset($config_vals['config'])) {
                    $res = ng_mysql_json_safe_decode($config_vals['config']);
                    $this->global_view_var = $res;
                    $this->service->getCache()->set($cache_key,$this->global_view_var,3600);
                }
            }
            if(method_exists($this,'auth')) {
                $auth_reponse = $this->auth();
                if ($auth_reponse['status'] == false) {
                    //欠缺时间部分
                    $this->redirect($auth_reponse['url']);
                }

            }
        }
    }

    public function redirect($url)
    {
        header('Location:'.$url);
    }

    public function render($status,$mess,$data,$type='json',$template='') {
        $data = array_merge($this->global_view_var,$data);
        if ($template && substr($template,0,-10)!='.twig.html') {
            $template.= '.twig.html';
        }

        return new ResponeHelper($status,$mess,$data,$type,$template,'manager');
    }

    /**
     * 递归处理菜单
     * @param RequestHelper $req
     * @param $menus
     * @return mixed
     */
    protected function recursion_menus(RequestHelper $req,$menus)
    {
        if($menus) foreach ($menus as $key=>$val) {
            $mark = $val['app']=='manager' ? 'manager' : 'plugin';
            $path = [
                'mark' => $mark,
                'bid'  => $req->company_id,
                'pl_name'=>$val['app'],
            ];
            $query = [
                'mod'=>$val['model'],
                'act'=>$val['action']
            ];
            $menus[$key]['url'] = urlGen($req,$path,$query);
            if ($menus[$key]['items']) {
                $menus[$key]['items'] = $this->recursion_menus($req,$menus[$key]['items']);
            }
        }
        return $menus;
    }

    public function nav_default(RequestHelper $req,array $preData)
    {
        $model = new model\ManageMenuModel($this->service);
        $navs = $model->getNav();

        $default_menu_id = 0;
        if ($navs) {
            foreach ($navs as $key=>$val) {
                //是否加入数据检查

                //默认取第一个
                $default_menu_id = $default_menu_id ? $default_menu_id : $val['id'];
                $navs[$key]['active'] = '';
                if ($val['app']==$req->request_plugin && $val['model']==$req->module && $val['action']==$req->action) {

                    $navs[$key]['active'] = 'nav_active';
                    //适配到取正式的
                    $default_menu_id=$val['id'];
                }

                $path = [
                    'mark' => 'manager',
                    'bid'  => $req->company_id,
                    'pl_name'=>$val['app'],
                ];
                $query = [
                    'mod'=>$val['model'],
                    'act'=>$val['action']
                ];
                $navs[$key]['url'] = urlGen($req,$path,$query);
            }
        } else {
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'index',
                'act'=>'index',
            ];
            $navs[0]['name'] = '首页';
            $navs[0]['url'] = urlGen($req,$path,$query);
        }

        //获得子菜单
        if ($default_menu_id) {
            $subMenus = $model->getSubMenu($default_menu_id);
            $subMenus = $this->recursion_menus($req,$subMenus);

        }

        if($subMenus) {
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>$subMenus[0]['app'],
            ];
            $query = [
                'mod'=>$subMenus[0]['model'],
                'act'=>$subMenus[0]['action']
            ];
        } else {
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'index',
                'act'=>'info'
            ];
        }


        $default_frame_url = urlGen($req,$path,$query,true);
        $default_frame_name = '首页';

        $data = [
            'bid'=>$req->company_id,
            'pl_name'=>$req->request_plugin,
            'mod'=> $req->module,
            'act'=>$req->action,
            'navs' => $navs,
            'submenu'=>$subMenus,
            'sessions'=>$this->sessions,
            'default_frame_url'=>$default_frame_url,
            'default_frame_name'=>$default_frame_name,
        ];
        return $data;

    }

    /**
     * 上传设置
     * @return array
     */
    protected function upload_setting()
    {
        $upload_setting = array(
            'image' => array(
                'upload_max_filesize' => '10240',//单位KB
                'extensions' => 'jpg,jpeg,png,gif,bmp4'
            ),
            'video' => array(
                'upload_max_filesize' => '10240',
                'extensions' => 'mp4,avi,wmv,rm,rmvb,mkv'
            ),
            'audio' => array(
                'upload_max_filesize' => '10240',
                'extensions' => 'mp3,wma,wav'
            ),
            'file' => array(
                'upload_max_filesize' => '10240',
                'extensions' => 'txt,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar'
            )
        );
        foreach ($upload_setting as $setting){
            $extensions=explode(',', trim($setting['extensions']));
            if(!empty($extensions)){
                $upload_max_filesize=intval($setting['upload_max_filesize'])*1024;//转化成KB
                foreach ($extensions as $ext){
                    if(!isset($upload_max_filesize_setting[$ext]) || $upload_max_filesize>$upload_max_filesize_setting[$ext]*1024){
                        $upload_max_filesize_setting[$ext]=$upload_max_filesize;
                    }
                }
            }
        }

        $upload_setting['upload_max_filesize']=$upload_max_filesize_setting;
        return $upload_setting;
    }

    /**
     * 构造树形数组
     * @param RequestHelper $req
     * @param array $preData
     */

    protected function buildTree($data,$parentid=0,&$tree,$path='')
    {

        if (isset($path)) {
            $path = $path.'/'.$parentid;
        } else {
            $path = 0;
        }
        foreach ($data as $key=>$val) {
            if($parentid == $val['parentid']) {
                $id = $val['id'];

                if (!$val['items'])$val['items']=[];

                $val['path'] = $path;
                $this->buildTree($data,$id,$val['items'],$path);
                $val['subcount'] = count($val['items']);
                $tree[] = $val;
            }
        }
    }

    /**
     * @param $tree
     * @param $myself_id
     * @return array
     */
    protected function selectTree($tree,$myself_id,$space='',$root=false,$disable='',$withoutMyself=false)
    {
        $option = [];
        if ($root) {
            $option[] = [
                'id'=>0,
                'name'=>'根',
                'space'=>'',
                'level_str'=>'',
                'disabled'=>$disable,
            ];
            $space = $space."&nbsp;&nbsp;&nbsp;&nbsp;";
        }

        if($tree ) {
            foreach($tree as $tree) {
                 $option_item = [
                    'id'=>$tree['id'],
                    'name'=>$tree['name'],
                    'space'=>$space,
                    'level_str'=>'└─',
                    'disabled'=> $disable,
                ];
                if ($withoutMyself) {
                    if($myself_id==$tree['id'] && $myself_id>0) {
                        $option_item['disabled'] ='disabled="disabled"';
                        $sub_disable = 'disabled="disabled"';
                    }
                } else {
                    $disable = '';
                    $sub_disable='';
                }

                $option[] = $option_item;
                if ($tree['subcount']>0) {
                    $sub_space= $space."&nbsp;&nbsp;&nbsp;&nbsp;";
                    $sub_option = $this->selectTree($tree['items'],$myself_id,$sub_space,false,$sub_disable,$withoutMyself);
                    $option = array_merge($option,$sub_option);
                }
                $sub_disable = '';


            }
        }
        return $option;
    }
    /**
     * 获取cdn的地址头
     * @return string
     */
    protected function getCdnHost($host='')
    {
        $cdn_prefix = $host."/wxapp/data";
        return $cdn_prefix;
    }

    protected function page($pageLink = '',$total_size = 1, $page_size = 0, $current_page = 1, $listRows = 6, $pageParam = '',  $static = false) {
        if ($page_size == 0) {
            $page_size = 20;
        }

        if (empty($pageParam)) {
            $pageParam = 'p';
        }

        $page = new Page($total_size, $page_size, $current_page, $listRows, $pageParam, $pageLink, $static);
        $page->SetPager('Manager', '{first}{prev}&nbsp;{liststart}{list}&nbsp;{next}{last}<span>共{recordcount}条数据</span>', array("listlong" => "4", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => ""));
        return $page;
    }
}