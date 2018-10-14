<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/7/27
 * Time: 上午12:20
 */

namespace manager\model;


use libs\asyncme\Service;

/**
 * Class AssetsModel
 * @package manager\model
 * @name 业务模版与应用关系模型类
 */
class WorksAppTemplateRelModel extends ManagerModel
{


    protected $app_template_rel_table = 'works_app_template_rel';

    /**
     * @name app模版引用列表
     * @param array $where
     * @param array $order
     * @param int $page
     * @param int $per_page
     * @param bool $raw
     * @return mixed
     */
    public function worksAppTemplateRelLists($where=[],$order=[],$page=1,$per_page=20,$raw=false)
    {
        return $this->tableLists($this->app_template_rel_table,$where,$order,$page,$per_page,$raw);
    }

    /**
     * @name app模版引用数量
     * @param array $where
     * @param bool $raw
     * @return mixed
     */
    public function worksAppTemplateRelCount($where=[],$raw=false)
    {
        return $this->tableCount($this->app_template_rel_table,$where,$raw);
    }

    /**
     * @name app模版引用信息
     * @param array $where
     * @return array
     */
    public function worksAppTemplateRelInfo($where=[])
    {
        $res = $this->db->table($this->app_template_rel_table)->where($where)->first();
        if ($res) {
            $res = (array) $res;
        }
        return $res;
    }

    /**
     * @name 添加app模版引用
     * @param $map
     * @return mixed
     */
    public function addWorksAppTemplateRel($map)
    {

        if (!$map['mtime']) $map['mtime'] = time();

        $flag = $this->db->table($this->app_template_rel_table)->insertGetId($map);
        return $flag;
    }

    /**
     * @name 保存app模版引用
     * @param array $where
     * @param $map
     * @return mixed
     * @throws \Exception
     */
    public function saveWorksAppTemplateRel($where=[],$map)
    {

        if (!$map['mtime']) $map['mtime'] = time();
        $flag = $this->db->table($this->app_template_rel_table)->where($where)->update($map);

        return $flag;
    }

    /**
     * @name 物理删除
     * @param $where
     * @param bool $raw
     * @return mixed
     * @throws \Exception
     */
    public function deleteWorksAppTemplateRel($where,$raw=false)
    {

        $obj = $this->db->table($this->app_template_rel_table);
        if (!$raw) {

            $obj=$obj->where($where);
        } else {

            $obj=$obj->whereRaw($where[0],$where[1]);
        }
        return $obj->delete();
    }
    
}