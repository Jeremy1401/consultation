<?php
/**
 * 会诊申请--控制器
 * Created by shiren.
 * time 2017.10.19
 */
namespace app\controller;

class Apply extends Common
{
    public $exportCols = [];
    public $colsText = [];

    /**
     * 会诊申请
     * @return \think\response\View
     */
    public function index()
    {
        $select=['id,name'];
        $hospital = D('Hospital')->getHospital($select,[]);
        return view('', ['hospital' => $hospital]);
    }

    /**
     * 会诊申请
     * @return \think\response\View
     */
    public function channel()
    {
        $select=['id,name'];
        $hospital = D('Hospital')->getHospital($select,[]);
        return view('', ['hospital' => $hospital]);
    }

    /**
     * 获取会诊申请列表
     */
    public function getApplyList(){
        $params = input('post.');
        // 获取当前登陆的用户id，根据此id查询表，返回结果
        $ret = ['error_code' => 0, 'data' => [], 'msg' => ""];
        if(!empty($params)){
            $apply_type = input('post.apply_type','-1');
            $apply_project = input('post.apply_project','-1');
            $status = input('post.status','-1');
            $is_charge = input('post.is_charge','-1');
            $apply_date = input('post.apply_date_str','');
            $hospital = input('post.hospital','-1');
            $keywords = input('post.keywords','');
            $green_channel = input('post.is_green_channel', 0);
            $cond_and = [];
            $cond_or = [];
            if($apply_type!=-1){
                $cond_and['apply_type'] = $apply_type;
            }
            if($apply_project!=-1){
                $cond_and['apply_type'] = $apply_project;
            }
            if($status != -1){
                $cond_and['a.status'] = $status;
            }
            if($is_charge != -1){
                $cond_and['is_charge'] = $is_charge;
            }
            if($apply_date){
                $cond_and['apply_date'] = strtotime($apply_date);
            }
            if($hospital!=-1){
                $cond_and['e.id'] = $hospital;
            }
            if($keywords){
                $cond_or['other_apply_project|e.name|c.name|c.phone'] = ['like','%'. myTrim($keywords) .'%'];
            }
            $cond_and['a.is_green_channel'] = $green_channel;
            $user_id = $this->getUserId();
            $select = ['b.id as doctor_id'];
            $cond['a.id'] = ['=',$user_id];
            $user_doctor_id = D('UserAdmin')->getUserAdmin($select,$cond);
            //$cond_and['c.id'] = ['=',$user_doctor_id['doctor_id']];

            $list = D('Apply')->getList($cond_or,$cond_and,[]);
            $page = input('post.current_page',0);
            $per_page = input('post.per_page',0);
            //分页时需要获取记录总数，键值为 total
            $ret["total"] = count($list);
            //根据传递过来的分页偏移量和分页量截取模拟分页 rows 可以根据前端的 dataField 来设置
            $ret["data"] = array_slice($list, ($page-1)*$per_page, $per_page);
            $ret['current_page'] = $page;
        }
        $this->jsonReturn($ret);
    }

    /**
     * 新建会诊申请
     */
    public function create(){
        $params = input('post.');
        if(!empty($params)) {
            $data = [];
            $ret = ['error_code' => 0, 'msg' => '新建成功'];
            //申请目标
            $data['apply_date_str'] = input('post.apply_date');
            $data['source_user_id'] = $this->getUserId();
            $data['apply_project'] = (int)input('post.apply_project');
            $data['apply_type'] = (int)input('post.apply_type', '2');
            $data['target_hospital_id'] = (int)input('post.consultation_hospital');
            $data['patient_id'] = (int)input('post.patient_id',-1);

            if (!isset($params['office_ids'])) {
                $office_ids = [];
            } else{
                $office_ids = $params['office_ids'];
            }

            if (!isset($params['doctor_ids'])) {
                $doctor_ids = [];
            }else{
                $doctor_ids = $params['doctor_ids'];
            }
            $data['is_definite_purpose'] = 0;
            if(!empty($office_ids)){
                $data['is_definite_purpose'] = 1;
            }
            $data['target_doctor_ids'] = '-';
            foreach ($doctor_ids as $id){
                $data['target_doctor_ids'] = $data['target_doctor_ids'].$id.'-';
            }
            $data['target_office_ids'] = '-';
            foreach ($office_ids as $id){
                $data['target_office_ids'] = $data['target_office_ids'].$id.'-';
            }

            $data['consultation_goal'] = input('post.consultation_goal', '');
            $data['other_apply_project'] = input('post.other_apply_project', '');
            $ret['data']=$data;
            //如果病患不存在，手动输入
            if ($data['patient_id'] == -1) {
                $patient = [];
                $patient['name'] = input('post.patient_name');
                $patient['ID_number'] = input('post.patient_ID_number');
                $patient['gender'] = input('post.patient_gender');
                $patient['age'] = input('post.patient_age');
                $patient['phone'] = input('post.patient_phone');
                $patient['ill_state'] = input('post.patient_illness_state');
                $patient['ill_type'] = input('post.patient_eyes_type');
                $patient['diagnose_state'] = input('post.diagnose_state');
                $patient['vision_left'] = input('post.patient_vision_left');
                $patient['vision_right'] = input('post.patient_vision_right');
                $patient['pressure_left'] = input('post.patient_pressure_left');
                $patient['pressure_right'] = input('post.patient_pressure_right');
                $patient['eye_photo_left'] = input('post.eye_photo_left');
                $patient['eye_photo_right'] = input('post.eye_photo_right');
                $patient['other_ill_type'] = input('post.other_ill_type','');
                $patient['eye_photo_left_origin']= input('post.eye_photo_left_origin');
                $patient['eye_photo_right_origin']= input('post.eye_photo_right_origin');
                $patient['files_path'] = input('post.files_path');
                $patient['files_path_origin'] = input('post.files_path_origin');
                $res = D('Patient')->addData($patient);
                if(!empty($res['errors'])){
                    $ret = ['error_code' => 2,
                            'msg' => '病人信息不全',
                            'errors' =>$res['errors'] ];
                    $this->jsonReturn($ret);
                }
                $data['patient_id'] = $res['id'];
            }

            $res = D('Apply')->addData($data);
            if(!empty($res['errors'])){
                $ret['error_code'] = 2;
                $ret['errors'] = $res['errors'];
            }

            $this->jsonReturn($ret);
        }
        $select = ['id,name'];
        $cond['role'] = ['=',1];
        $hospital = D('Hospital')->getHospital($select,$cond);
        $hospital_id = $hospital[0]['id'];
        $hospital_office = D('HospitalOffice')->getList(['hospital_id' => $hospital_id]);
        $office = [];
        for($i=0;$i<count($hospital_office);$i++){
            $office_id = $hospital_office[$i]['office_id'];
            array_push($office, D('Office')->getOffice($select,['id'=>$office_id]));
        }
        $hospital_office_id = $hospital_office[0]['id'];
        $doctor = D('Doctor')->getList(['hospital_office_id' => $hospital_office_id]);

        $apply_info['apply_hospital_name'] ='某县级医院';
        $apply_info['apply_doctor_name'] ='某县级医院医生';
        $apply_info['apply_doctor_phone'] = '15115062214';
        $apply_info['date']='2017/11/16';
        return view('', ['hospital' => $hospital,'office' => $office, 'doctor' => $doctor,'apply_info'=>$apply_info]);
    }

    /**
     * 会诊申请
     * @return \think\response\View
     */
    public function info(){
        $id = input('get.id');
        return view('', ['id' => $id]);
    }

    /**
     * 获取申请详情
     */
    public function getApplyInfo(){
        $id = input('post.id');
        $ret = ['error_code' => 0, 'msg' => ''];
        $res = D('Apply')->getStatusById($id);
        if($res['status'] == 1){
            try{
                //D('Apply')->UpdateStatus(['id' => ['=', $id]], 2);
            }catch(MyException $e){
                $ret['error_code'] = 1;
                $ret['msg'] = '标记失败';
            }
        }
        $apply_info = D('Apply')->getById($id);
        $patient_id = $apply_info['patient_id'];
        $source_user_id = $apply_info['source_user_id'];
        // 获取目标医院信息
        $target_hospital_id = $apply_info['target_hospital_id'];
        $target_hospital_info = D('Hospital')->getById($target_hospital_id);

        $ret['target_hospital_info'] = $target_hospital_info;
        $ret['target_doctor_info'] = [];
        $ret['target_office_info'] = [];
        $ret['debug'] = [];
        $target_doctor_ids = $apply_info['target_doctor_ids'];
        $target_office_ids = $apply_info['target_office_ids'];
        $array_target_doctor_id = explode('-',$target_doctor_ids);
        for($index=0;$index<count($array_target_doctor_id);$index++) {
            array_push($ret['debug'], $array_target_doctor_id[$index]);
            if($array_target_doctor_id[$index] != ''){
                array_push($ret['target_doctor_info'], D('Doctor')->getById((int)$array_target_doctor_id[$index]));
            }
        }
        $array_target_office_id = explode('-',$target_office_ids);
        for($index=0;$index<count($array_target_office_id);$index++) {
            if($array_target_office_id[$index] != ''){
                array_push($ret['target_office_info'], D('Office')->getById((int)$array_target_office_id[$index]));
            }
        }

        $source_user_info = D('UserAdmin')->getById($source_user_id);
        $source_doctor_id = $source_user_info['doctor_id'];
        $source_doctor_info = D('Doctor')->getById($source_doctor_id);
        $source_hospital_office_id = $source_doctor_info['hospital_office_id'];
        $source_hospital_office = D('HospitalOffice')->getById($source_hospital_office_id);
        $source_hospital_id = $source_hospital_office['hospital_id'];
        $source_office_id = $source_hospital_office['office_id'];

        $source_hospital_info = D('Hospital')->getById($source_hospital_id);
        $source_office_info = D('Office')->getById($source_office_id);

        $patient_info = D('Patient')->getById($patient_id);

        $ret['apply_info'] = $apply_info;
        $ret['patient_info'] = $patient_info;
        $ret['source_hospital_info'] = $source_hospital_info;
        $ret['source_office_info'] = $source_office_info;
        $ret['source_doctor_info'] = $source_doctor_info;
        $this->jsonReturn($ret);
    }

    /**
     * 回复申请
     */
    public function reply(){
        $data = input('post.');
        if(!empty($data)){
            $ret = ['error_code' => 0, 'msg' => '回复成功'];
            $id = $data['id'];
            if($data['status'] == 4){
                $data = [];
                $data['id'] = $id;
                $data['consultation_result'] = '很抱歉，您的会诊申请被拒绝！';
                $data['status'] = 4;
            }
            $res = D('Apply')->saveData($id, $data);
            if(!empty($res['errors'])){
                $ret['debug'] = !empty($ret['errors']);
                $ret['error_code'] = 1;
                $ret['errors'] = $res['errors'];
                $ret['msg'] = '回复失败';
            }
            $this->jsonReturn($ret);
        }
        $id = input('get.id');
        $status = D('Apply')->getStatusById($id);
        return view('', ['id' => $id, 'status' => $status]);
    }

    /**
     * 删除会诊申请
     */
    public function remove(){
        $ret = ['code' => 1, 'msg' => '删除成功'];
        $ids = input('post.ids');
        try{
             D('Apply')->remove(['id' => ['in', $ids]]);
        }catch(MyException $e){
            $ret['code'] = 2;
            $ret['msg'] = '删除失败';
        }
        $this->jsonReturn($ret);
    }

    /**
     * 编辑会诊申请
     */
    public function edit(){
        $id = input('get.id');
        $data = input('post.');
        $apply = D('Apply')->getById($id);
        if(!empty($data)){
            $ret['data'] = $data;
            $this->jsonReturn($ret);
        }
        return view('',['apply' => $apply]);
    }

    /**
     * 绿色通道申请
     * @return \think\response\View
     */
    public function channelInfo(){
        $id = input('get.id');
        return view('', ['id' => $id]);
    }

    /**
     * 回复绿色通道申请
     */
    public function channelReply(){
        $data = input('post.');
        if(!empty($data)){
            $ret = ['error_code' => 0, 'msg' => '回复成功'];
            $id = $data['id'];
            if($data['status'] == 4){
                $data = [];
                $data['id'] = $id;
                $data['consultation_result'] = '很抱歉，您的会诊申请被拒绝！';
                $data['status'] = 4;
            }
            $res = D('Apply')->saveData($id, $data);
            if(!empty($res['errors'])){
                $ret['debug'] = !empty($ret['errors']);
                $ret['error_code'] = 1;
                $ret['errors'] = $res['errors'];
                $ret['msg'] = '回复失败';
            }
            $this->jsonReturn($ret);
        }
        $id = input('get.id');
        $status = D('Apply')->getStatusById($id);
        return view('', ['id' => $id, 'status' => $status]);
    }

    /**
     * 新建会诊申请
     */
    public function channelCreate(){
        $params = input('post.');
        if(!empty($params)) {
            $data = [];
            $ret = ['error_code' => 0, 'msg' => '新建成功'];
            //申请目标
            $data['apply_date_str'] = input('post.apply_date');
            $data['source_user_id'] = $this->getUserId();
            $data['apply_project'] = (int)input('post.apply_project');
            $data['apply_type'] = (int)input('post.apply_type', '2');
            $data['target_hospital_id'] = (int)input('post.consultation_hospital');
            $data['patient_id'] = (int)input('post.patient_id',-1);

            if (!isset($params['office_ids'])) {
                $office_ids = [];
            } else{
                $office_ids = $params['office_ids'];
            }

            if (!isset($params['doctor_ids'])) {
                $doctor_ids = [];
            }else{
                $doctor_ids = $params['doctor_ids'];
            }
            $data['is_definite_purpose'] = 0;
            if(!empty($office_ids)){
                $data['is_definite_purpose'] = 1;
            }
            $data['target_doctor_ids'] = '-';
            foreach ($doctor_ids as $id){
                $data['target_doctor_ids'] = $data['target_doctor_ids'].$id.'-';
            }
            $data['target_office_ids'] = '-';
            foreach ($office_ids as $id){
                $data['target_office_ids'] = $data['target_office_ids'].$id.'-';
            }

            $data['consultation_goal'] = input('post.consultation_goal', '');
            $data['other_apply_project'] = input('post.other_apply_project', '');
            $ret['data']=$data;
            //如果病患不存在，手动输入
            if ($data['patient_id'] == -1) {
                $patient = [];
                $patient['name'] = input('post.patient_name');
                $patient['ID_number'] = input('post.patient_ID_number');
                $patient['gender'] = input('post.patient_gender');
                $patient['age'] = input('post.patient_age');
                $patient['phone'] = input('post.patient_phone');
                $patient['ill_state'] = input('post.patient_illness_state');
                $patient['ill_type'] = input('post.patient_eyes_type');
                $patient['diagnose_state'] = input('post.diagnose_state');
                $patient['vision_left'] = input('post.patient_vision_left');
                $patient['vision_right'] = input('post.patient_vision_right');
                $patient['pressure_left'] = input('post.patient_pressure_left');
                $patient['pressure_right'] = input('post.patient_pressure_right');
                $patient['eye_photo_left'] = input('post.eye_photo_left');
                $patient['eye_photo_right'] = input('post.eye_photo_right');
                $patient['other_ill_type'] = input('post.other_ill_type','');
                $patient['eye_photo_left_origin']= input('post.eye_photo_left_origin');
                $patient['eye_photo_right_origin']= input('post.eye_photo_right_origin');
                $patient['files_path'] = input('post.files_path');
                $patient['files_path_origin'] = input('post.files_path_origin');
                $res = D('Patient')->addData($patient);
                if(!empty($res['errors'])){
                    $ret = ['error_code' => 2,
                        'msg' => '病人信息不全',
                        'errors' =>$res['errors'] ];
                    $this->jsonReturn($ret);
                }
                $data['patient_id'] = $res['id'];
            }

            $res = D('Apply')->addData($data);
            if(!empty($res['errors'])){
                $ret['error_code'] = 2;
                $ret['errors'] = $res['errors'];
            }

            $this->jsonReturn($ret);
        }
        $select = ['id,name'];
        $cond['role'] = ['=',1];
        $hospital = D('Hospital')->getHospital($select,$cond);
        $hospital_id = $hospital[0]['id'];
        $hospital_office = D('HospitalOffice')->getList(['hospital_id' => $hospital_id]);
        $office = [];
        for($i=0;$i<count($hospital_office);$i++){
            $office_id = $hospital_office[$i]['office_id'];
            array_push($office, D('Office')->getOffice($select,['id'=>$office_id]));
        }
        $hospital_office_id = $hospital_office[0]['id'];
        $doctor = D('Doctor')->getList(['hospital_office_id' => $hospital_office_id]);
        return view('', ['hospital' => $hospital,'office' => $office, 'doctor' => $doctor]);
    }

    /**
     * 编辑会诊申请
     */
    public function channelEdit(){
        $id = input('get.id');
        $data = input('post.');
        $Apply = D('Apply')->getById($id);
        if(!empty($data)){
            $ret['data'] = $data;
            $this->jsonReturn($ret);
        }
        return view('',['Apply' => $Apply]);
    }


}