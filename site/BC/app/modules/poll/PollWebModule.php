<?php
/**
  * @package Module
  * @subpackage Poll
  */

/**
  * @package Module
  * @subpackage Poll
  */
class PollWebModule extends WebModule {
  protected $id = 'poll';

  protected function getFirstQuestionId($poll_data){
		foreach ($poll_data as $question_data ){
			return $question_data['question_id'];
		}
		return  0;
  }
  protected function getNextQuestionId($poll_data, $current_id){
	//print_r($poll_data);

		$next_id = 0;
		$matched = 0;
		foreach ($poll_data as $question_data ){
			 if ($matched == 1 && $current_id != $question_data['question_id']){
			 	return $question_data['question_id'];
			 }
			 else{
			 	if ($current_id ==  $question_data['question_id']){
			 		$matched = 1;
			 	}
			 }
		}
		return 0;
  }

  protected function initializeForPage() {
    require_once('MysqlCalls.php');

  	$mysqlConnection = get_mysql_connection_string();


  	$ret = get_poll(1,  $mysqlConnection);

  	$poll_data = json_decode($ret, true);

	$prev_question = NULL;
	$ret_string = '';
	$idx = 1;

  	// get current selection
  	$curr_question = 0;
	if (isset($_POST["current-question"])){
		$curr_question = $_POST["current-question"];
	}
	else
	if (isset($_GET["current-question"])){
		$curr_question = $_GET["current-question"];
	}

	if (0== $curr_question){
		$curr_question = $this->getFirstQuestionId($poll_data);
	}
	$next_question = $this->getNextQuestionId($poll_data, $curr_question);

    switch ($this->page) {
      // first page
      case 'index':
		//print_r($poll_data);


		$ret_string = $ret_string.'<form method="post" id="pollform" action="/poll/result">';
		$ret_string = $ret_string.'<input type="hidden" name="current-question" id="current-question" value="'.$curr_question.'"/> ';
		$ret_string = $ret_string.'<div class="poll-header">';

		$loopIdx = 0;

		foreach ($poll_data as $question_data ){
			//print_r($question_data);

			// question
			$question = $question_data["question"];
			$answer =  $question_data['answer'];
			$question_id = $question_data['question_id'];
			$answer_id = $question_data['id'];
			//echo "question-id=".$question_id.;

			if ($curr_question != $question_id){
				continue;
			}

			if (is_null($prev_question) || $prev_question != $question){
				$ret_string = $ret_string.'<div class="poll-question" id="question1-'.$question_id.'">';
				$ret_string = $ret_string.$question;
			}

			// answer
			$ret_string = $ret_string.'<div class="poll-answer" id="answer1-'.$answer_id.'">';
			//$ret_string = $ret_string.'<input type="radio" name="'.$question_id.'" value="'.$answer_id.'">';
			//$ret_string = $ret_string.'<input type="radio" name="'.$question_id.'" value="'.$answer_id.'">';
			$ret_string = $ret_string.'<a href="/poll/result?'.$answer_id.'=1&current-question='.$curr_question.'">'.$answer.'</a>';
			//$ret_string = $ret_string.$answer;
			//$ret_string = $ret_string.' <input type="submit"  name="'.$answer_id.'" value=">>"';
			$ret_string = $ret_string.'</div>';

			//question closing
			if (is_null($prev_question) || $prev_question != $question){
				$prev_question = $question;
				$ret_string = $ret_string.'</div>';
			}
			$idx++;
		}
		//$ret_string = $ret_string.'<br><div class="poll-button"> <input type="submit" value="Next"> </div>';
		//$ret_string = $ret_string.'<br><div class="poll-button"> <input type="submit" value="Submit Poll"> <a href="/poll/viewResult">Result</a></div>';

		$ret_string = $ret_string.'<br><div class="poll-button"> <input type="submit" value="Next"> </div>';
		$ret_string = $ret_string.'</form>';
		// poll-header closing
		$ret_string = $ret_string.'</div>';

		$this->assign('message', $ret_string);
		break;

	  // post result
	  case 'result':

		foreach ($poll_data as $question_data ){
			$question_id = $question_data['question_id'];
			$answer_id = $question_data['id'];
			$question = $question_data["question"];
			$answer =  $question_data['answer'];
			$value = NULL;
			/*
			if (isset($_POST[$question_id])){
				$value = $_POST[$question_id];
				if (!is_null($value) && $value == $answer_id && isset($answer_id) && isset($question_id)){
					update_poll($question_id, $answer_id, $mysqlConnection);
				}
			}
			else
			if (isset($_GET[$question_id])){
				$value = $_GET[$question_id];
				if (!is_null($value) && $value == $answer_id && isset($answer_id) && isset($question_id)){
					update_poll($question_id, $answer_id, $mysqlConnection);
				}
			}
			*/
			if (isset($_POST[$answer_id])){
					update_poll($question_id, $answer_id, $mysqlConnection);
			}
			else
			if (isset($_GET[$answer_id])){
					update_poll($question_id, $answer_id, $mysqlConnection);
			}

		}
	  case 'viewResult':
		$ret_string = $ret_string.'<form method="post" id="pollform" action="/poll/index">';
		$ret_string = $ret_string.'<input type="hidden" name="current-question" id="current-question" value="'.$next_question.'"/> ';

		//$ret_string = $ret_string.'<br><div class="poll-button"> <input type="submit" value="Next"> </div>';

		$ret_string = $ret_string.'<div class="poll-header">';


		$ret = get_poll(1,  $mysqlConnection);
		$poll_data = json_decode($ret, true);

		//javascript
  		$ret_string = $ret_string.'<script language="javascript"> function drawPercentBar(width, percent, color, background)';
  		$ret_string = $ret_string.'{';
    	$ret_string = $ret_string.'var pixels = width * (percent / 100); ';
    	$ret_string = $ret_string.'if (!background) { background = "none"; }';
    	$ret_string = $ret_string.'document.write("<div style=\"position: relative; line-height: 1em; background-color: " + background + "; border: 1px solid #D2B48C; width: " + width + "px\">");';
    	$ret_string = $ret_string.'document.write("<div style=\"height: 1.5em; width: " + pixels + "px; background-color: "+ color + ";\"></div>")';
    	$ret_string = $ret_string.'document.write("<div style=\"position: absolute; text-align: center; padding-top: .25em; width: " + width + "px; top: 0; left: 0\">" + percent + "%</div>");';
    	$ret_string = $ret_string.'document.write("</div>");';
  		$ret_string = $ret_string.'}</script> ';

		$ret_string = $ret_string.'<div class="poll-result-header">';
		foreach ($poll_data as $question_data ){
			//print_r($question_data);

			// question
			$question = $question_data["question"];
			$answer =  $question_data['answer'];
			$question_id = $question_data['question_id'];
			$answer_id = $question_data['id'];

			if ($curr_question != $question_id){
				continue;
			}

			$total = $question_data['total'];
			if (is_null($total))
				$total = 0;
			$selected = $question_data['selected'];
			if (is_null($selected))
				$selected = 0;

			//echo "question-id=".$question_id.;

			if (is_null($prev_question) || $prev_question != $question){
				$ret_string = $ret_string.'<div class="poll-question" id="question2-'.$question_id.'">';
				$ret_string = $ret_string."<b>".$question."</b>(".$total." votes)";

			}

			// answer
				$ret_string = $ret_string.'<div class="poll-answer" id="answer2-'.$answer_id.'">';
//				$ret_string = $ret_string.'<input type="radio" name="'.$question_id.'" value="'.$answer_id.'">';
				$ret_string = $ret_string.$answer;
				// percentage
				if ($total != 0){
					$per = intval($selected *10000.0/$total);
					$per = $per *1.0/100;
					$ret_string = $ret_string.' &nbsp;'.'<script language="javascript">drawPercentBar(200, '.$per.', "#D2B48C", "#EFEBE1"); </script>';
					//$ret_string = $ret_string.' &nbsp; <b>'.sprintf("%01.2f", $per)."%</b> (".$selected.")";
				}
				$ret_string = $ret_string.'</div>';

			//question closing
			if (is_null($prev_question) || $prev_question != $question){
				$prev_question = $question;
				$ret_string = $ret_string.'</div>';

			}
			$idx++;
		}
		// poll-header closing
		$ret_string = $ret_string.'</div>';
		if ($next_question != 0){
			$ret_string = $ret_string.'<br><div class="poll-button"> <input type="submit" value="Next"> </div>';
		}
		$ret_string = $ret_string.'</form>';
		// poll-header closing
		$ret_string = $ret_string.'</div>';

		$this->assign('message', $ret_string);

	  	break;
	}
  }

}
