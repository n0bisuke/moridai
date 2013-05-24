<?php
/* [Moridai Project]
 * 2013年5月
 * @n0bisuke @otukutun @furu222 @omega999
 * CakePHP v2.3.5
 */
class MoridaiController extends AppController {
  public $name = 'Moridai'; //class name
	public $uses = array('MoridaiUser','MoridaiQuestion','MoridaiHistory','MoridaiCategory');
	var $components = array('RequestHandler');
	public $layout = null;
	
	//UseraddAPI(ユーザ追加) POST
	function useradd(){
		if($this->params['form']['user_name'] != null &&
			$this->params['form']['facebook_id'] != null){//empty checking(not null post)
			
			$this->data['MoridaiUser']['user_name'] = $this->params['form']['user_name']; //userID
			$this->data['MoridaiUser']['facebook_id'] = $this->params['form']['facebook_id']; //facebookID
			
			if ($this->MoridaiUser->save($this->data)){
            	$output['response'] = 'Saved';
        	} else {
            	$output['response'] = 'Error';
			}
		}else{//null post
            $output['response'] = 'Data is Empty';
		}
		$this->set(compact('output'));
	}
	
	//AnswerCheckAPI (正誤情報をサーバーへ連絡) POST
	function answer_check(){
		if($this->params['form']['user_id'] != null &&
			$this->params['form']['question_id'] != null &&
			$this->params['form']['category_id'] != null &&
			$this->params['form']['answer_flag'] != null &&
			$this->params['form']['answer_option'] != null &&
			$this->params['form']['answer_type'] != null){//empty checking(not null post)
			
			$this->data['MoridaiHistory']['user_id'] = $this->params['form']['user_id'];
			$this->data['MoridaiHistory']['question_id'] = $this->params['form']['question_id'];
			$this->data['MoridaiHistory']['category_id'] = $this->params['form']['category_id'];
			$this->data['MoridaiHistory']['answer_flag'] = $this->params['form']['answer_flag'];
			$this->data['MoridaiHistory']['answer_option'] = $this->params['form']['answer_option'];
			$this->data['MoridaiHistory']['answer_type'] = $this->params['form']['answer_type'];
			
			if ($this->MoridaiHistory->save($this->data)){
            	$output['response'] = 'Saved';
        	} else {
            	$output['response'] = 'Error';
			}
		}else{//null post
            $output['response'] = 'Data is Empty';
		}
		$this->set(compact('output'));
		$this->render('useradd');//useraddAPIと共通
	}

	//QuestionAPI (出題) GET
    function question(){
    	if($this->params['url']['user_id'] != null &&
			$this->params['url']['category_id'] != null){
			
    		//指定カテゴリ内で正解した問題検索 ※1
			$data = $this->MoridaiHistory->find('all', array(
				'conditions' => 
				array('MoridaiHistory.user_id' => $this->params['url']['user_id'], //指定ユーザ$user_idが回答した問題
					'MoridaiHistory.answer_flag' => 1 //なおかつ正解した問題					
					),
				'fields' => Array('MoridaiHistory.question_id')//フィールド指定
			));
			//過去に回答履歴がある
			if(!empty($data)){
				//カテゴリ内の全問題
				$alldata = $this->MoridaiQuestion->find('all',array('conditions' =>
					 array('MoridaiQuestion.category_id' => $this->params['url']['category_id'])));
				$allcount = count($alldata); //カテゴリ内の全問題数
				$quizcount = count($data); //正解した問題数
				if($quizcount==$allcount){//全ての問題に回答済
					$output['response'] = "finish";
				}else{//回答途中
					//クエリ作成
					$sql = "SELECT * FROM  `moridai_questions` WHERE";
					foreach ($data as $key => $value) {
						if($key == 0){
							$sql .= " `id` !=".$value['MoridaiHistory']['question_id'];
							$sql .= " AND `category_id` =".$this->params['url']['category_id'];
						}else{
							$sql .= " AND  `id` !=".$value['MoridaiHistory']['question_id'];
						}
					}
					$sql .= " ORDER BY RAND( ) limit 1"; //一件 ランダムに
					$recipe1 = $this->MoridaiQuestion->query($sql);
				
					$recipe['MoridaiQuestion']['id'] = $recipe1[0]['moridai_questions']['id'];
					$recipe['MoridaiQuestion']['question'] = $recipe1[0]['moridai_questions']['question'];
					$recipe['MoridaiQuestion']['option1'] = $recipe1[0]['moridai_questions']['option1'];
					$recipe['MoridaiQuestion']['option2'] = $recipe1[0]['moridai_questions']['option2'];
					$recipe['MoridaiQuestion']['option3'] = $recipe1[0]['moridai_questions']['option3'];
					$recipe['MoridaiQuestion']['option4'] = $recipe1[0]['moridai_questions']['option4'];
					$recipe['MoridaiQuestion']['right_answer'] = $recipe1[0]['moridai_questions']['right_answer'];
					$recipe['MoridaiQuestion']['description'] = $recipe1[0]['moridai_questions']['description'];
					$recipe['MoridaiQuestion']['category_id'] = $recipe1[0]['moridai_questions']['category_id'];
					$output['response'] = $recipe;
				}
			}else{//初回答の場合はDBからランダムに1件指定したカテゴリの問題を取得
        		$output['response'] = $this->MoridaiQuestion->find('all',array('conditions' =>
					 array('MoridaiQuestion.category_id' => $this->params['url']['category_id']),
					 'limit' => 1,'order' => 'rand()'));
        	}
        }else{//null post
       		$output['response'] = 'Data is Empty';
        }
		//debug($output);
        $this->set(compact('output'));
		$this->render('useradd');//useraddAPIと共通
    }
	
	//TestAPI (試験をします) GET
	function test(){
		//DB内の設問をランダムに全て取得
		$data = $this->MoridaiQuestion->find('all', array('order' => 'rand()'/*,'limit'=>'5'*/));
		for($i=0; $i < count($data) ; $i++){
			$data[$i]['MoridaiQuestion']['category_name'] = $data[$i]['MoridaiCategory']['name'];
			unset($data[$i]['MoridaiCategory']);
		}
		if(!empty($data)){
			$output['response'] = $data;
		}else{//データが取得できなかった
            $output['response'] = 'Data is Empty';
		}
		$this->set(compact('output'));
		$this->render('useradd');//useraddAPIと共通
	}
}
?>

