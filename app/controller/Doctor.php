<?php
/**
 * 医生信息--控制器
 * Created by shiren.
 * time 2017.10.19
 */
namespace app\controller;

class Doctor extends Common
{
    public $exportCols = [];
    public $colsText = [];

    /**
     * 医生信息
     * @return \think\response\View
     */
    public function index()
    {
        return view('', []);
    }

    /**
     * 获取医生信息列表
     */
    public function getDoctorList(){
        $params = input('post.');
        // 获取当前登陆的用户id，根据此id查询表，返回结果
        $user_id = $this->getUserId();
        $ret = ['error_code' => 0, 'data' => [], 'msg' => ""];
        $cond['target_user_id'] = ['=', $user_id];
        //$list = D('Doctor')->getList($cond);
        $list = [];
        $list[0] = ['id' => 1, 'hospital_id' => 1, 'hospital_name' => '医院甲',
            'office_id' => 1, 'office_name' => '眼科', 'name' => '张三',
            'position' => '教授，博导', 'phone' => '135210263021', 'email' => '121212@11.com',
            'address' => '湖南长沙'
        ];
        $list[1] = ['id' => 2, 'hospital_id' => 1, 'hospital_name' => '医院甲',
            'office_id' => 1, 'office_name' => '眼科', 'name' => '李四',
            'position' => '教授，博导', 'phone' => '135210263021', 'email' => '121212@11.com',
            'address' => '湖南长沙'
        ];
        $page = input('post.current_page',0);
        $per_page = input('post.per_page',0);
        //分页时需要获取记录总数，键值为 total
        $ret["total"] = count($list);
        //根据传递过来的分页偏移量和分页量截取模拟分页 rows 可以根据前端的 dataField 来设置
        $ret["data"] = array_slice($list, ($page-1)*$per_page, $per_page);
        $ret['current_page'] = $page;
        $this->jsonReturn($ret);
    }

    /**
     * 删除医生信息
     */
    public function remove(){
        $ret = ['code' => 1, 'msg' => '删除成功'];
        $ids = input('post.ids');
        try{
            $res = D('Doctor')->remove(['id' => ['in', $ids]]);
        }catch(MyException $e){
            $ret['code'] = 2;
            $ret['msg'] = '删除失败';
        }
        $this->jsonReturn($ret);
    }

    /**
     * 新建医生信息
     */
    public function create(){
        $params = input('post.');
        $cond = [];
        $cond['id'] = ['<>', $this->getUserId()];
        $target_users = D('UserAdmin')->getList($cond);
        if(!empty($params)) {
            $data = [];
            $ret = ['code' => 1, 'msg' => '新建成功'];
            $title = input('post.title', '');
            $priority = input('post.priority', '');
            if (!isset($params['target_user_ids'])) {
                $params['target_user_ids'] = [];
            }
            if (!isset($params['content'])){
                $params['content'] = '';
            }

            $data['source_user_id'] = $this->getUserId();
            $data['title'] = $title;
            $data['content'] = $params['content'];
            $data['operation'] = '查看';
            $data['priority'] = (int)$priority;
            $data['status'] = 0;

            $dataSet = [];
            if(!empty($params['target_user_ids'])){
                for($i=0;$i<count($params['target_user_ids']);$i++){
                    $data['target_user_id'] = (int)$params['target_user_ids'][$i];
                    array_push($dataSet, $data);
                }
                // 添加Doctor
                $res_apply = D('Doctor')->addAllData($dataSet);
                if (!empty($res_apply['errors'])) {
                    $ret['code'] = 2;
                    $ret['msg'] = '新建失败';
                    $ret['errors'] = $res_apply['errors'];
                    $this->jsonReturn($ret);
                }
                $log['user_id'] = $this->getUserId();
                $log['IP'] = $this->getUserIp();
                $log['section'] = '医生信息';
                $log['action_descr'] = '添加医生信息';
                D('OperationLog')->addData($log);
                $this->jsonReturn($ret);
            }
            else{
                $data['target_user_id'] = '';
                // 添加Doctor
                $res_apply = D('Doctor')->addData($data);
                if (!empty($res_apply['errors'])) {
                    $ret['code'] = 2;
                    $ret['msg'] = '新建失败';
                    $ret['errors'] = $res_apply['errors'];
                }
                $this->jsonReturn($ret);
            }

        }
        $office = [];
        $office[0] = ['id' => 1, 'name' => '骨科'];
        $office[1] = ['id' => 2, 'name' => '眼科'];
        return view('', ['office' => $office]);
    }


    /**
     * 获取医生信息
     */
    function info(){
        $id = input('get.id');
        return view('', ['id' => $id]);
    }

    /**
     * 获取医生详情
     */
    public function getDoctorInfo(){
        $id = input('post.id');
        $ret = ['error_code' => 0, 'msg' => ''];
        $list = D('Doctor')->getById($id);
        $hospital_office_id = $list['hospital_office_id'];
        $hosiptal_office = D('HospitalOffice')->getById($hospital_office_id);
        $hospital_id = $hosiptal_office['hospital_id'];
        $office_id = $hosiptal_office['office_id'];
        $hospital_info = D('Hospital')->getById($hospital_id);
        $office_info = D('Office')->getById($office_id);
        $ret['info'] = $list;
        $ret['hospital'] = ['name' => $hospital_info['name']];
        $ret['office'] = ['name' => $office_info['name']];
        $this->jsonReturn($ret);
    }

}