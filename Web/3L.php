<?php
use MongoDB\Client;

class Page {
    public $smarty = null;
    public $db = null;

    public function __construct()
    {
        $client = new MongoDB\Client(DB_SERVER);
        $this->db = $client->selectDatabase(DB_NAME);
        $this->smarty = new Smarty();
        // DEBUG ONLY
        $this->smarty->caching = true;
        $this->smarty->template_dir = ROOT.'page';
        $this->smarty->compile_dir = ROOT.'tmp';
        $this->smarty->cache_dir = ROOT.'tmp';
    }

    public function index()
    {
        $db_exams = $this->db->selectCollection('exams');
        $exams = array_reverse($db_exams->find()->toArray());
        $this->smarty->assign('exams', $exams);
        $this->show('index');
    }

    public function exam($exam_id)
    {
        $db_exams = $this->db->selectCollection('exams');
        $exam = $db_exams->findOne(['id'=>$exam_id]);
        if (!$exam) {
            die('404');
        }
        $this->smarty->assign('exam', $exam);
        $this->show('exam');
    }

    public function query($exam_id)
    {
        $db_exams = $this->db->selectCollection('exams');
        $exam = $db_exams->findOne(['id'=>$exam_id]);
        if (!$exam) {
            die('404');
        }
        if (!isset($_POST['id'], $_POST['name'])) {
            die('404');
        }

        list($id, $name) = [$_POST['id'], $_POST['name']];
        $db_stu = $this->db->selectCollection('score_'.$exam_id);
        $student = $db_stu->findOne(['id'=>$id]);
        if (!$student || $name !== $student['name']) {
            die('输入信息错误！请返回上一页重试。');
        }

        $db_class = $this->db->selectCollection('class_'.$exam_id);
        $class = $db_class->findOne(['id'=>$student['class']]);
        $db_subject = $this->db->selectCollection('subject_'.$exam_id);
        $subjects = $db_subject->find()->toArray();
        $subjects_map = [];
        foreach ($subjects as $key => $subject) {
            $subjects_map[$subject['id']]['name'] = $subject['name'];
            $subjects_map[$subject['id']]['key'] = $key;
        }
        $db_question = $this->db->selectCollection('question_'.$exam_id);

        $questions = $db_question->find()->toArray();
        $questions_map = [];
        foreach ($questions as $question) {
            $questions_map[$question['id']] = $question['name'];
        }


        $score = [];
        foreach ($class['subjects'] as $subject_id) {
            $subject = $subjects_map[$subject_id]['name'];
            foreach ($subjects[$subjects_map[$subject_id]['key']]
                     ['questions'] as $question_id) {
                $question = $questions_map[$question_id];
                $score[$subject][$question] = $student['score'][$question_id];
            }
        }

        $student = [
            'id' => $student['id'],
            'name' => $student['name'],
            'class' => $class['name']
        ];

        $this->smarty->assign('exam', $exam);
        $this->smarty->assign('student', $student);
        $this->smarty->assign('score', $score);

        // Log it
        $db_log = $this->db->selectCollection('log_'.$exam_id);
        $log = [
            'ip' => isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ?
                $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER["REMOTE_ADDR"],
            'agent' => $_SERVER['HTTP_USER_AGENT'],
            'id' => $student['id'],
            'name' => $student['name'],
        ];
        $db_log->insertOne($log);
        $this->smarty->caching = false;

        $this->show('query');
    }

    public function show($page)
    {
        $this->smarty->display($page.'.tpl');
    }
}