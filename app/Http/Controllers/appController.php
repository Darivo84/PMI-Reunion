<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Auth;
use Hash;
use DB;
use Input;
use Validator;
use Redirect;
use Session;
use Response;
use Carbon\Carbon;
use App\User;
use App\Request;
use App\Question;
use App\Stats;
use App\Store;
use App\Competitor;
use App\CompetitorActivity;
use Storage;

class appController extends Controller
{
    /*
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
    */

    public function application(){
    	// GET CONTENTS
	        $POSTED_DATA = file_get_contents("php://input");
	        $REQUEST = json_decode($POSTED_DATA);  
	        @$TYPE = $REQUEST->type;
	        @$DATA = $REQUEST->data; 
	        @$VERSION = $REQUEST->app_version;
	        @$DATE = $REQUEST->datetime;

        switch ($TYPE) {
            case 'login':
                return $this->LOGIN($DATA->login_id,$DATA->password,$DATE,$VERSION);
                break; 
            case 'sync':
                return $this->SYNC($DATA,$DATE,$VERSION);
                break;  ; 
            case 'sync_theta':
                return $this->SYNC_THETA($DATA,$DATE,$VERSION);
                break; 
            case 'sync_new':
                return $this->SYNC_NEW($DATA,$DATE,$VERSION);
                break;                                                                                                
         }     
    }

    // MANAGE LOGIN OF USERS
        public function LOGIN($LOGIN,$PASS,$DATE,$VERSION)
        {
        	$USER = User::where('login_id',$LOGIN)->where('password',$PASS)->where('status','active')->first();
        	if(!empty($USER)){
        		$this->NEW_REQUEST('Login - Success',$USER->id,"Login : " . $LOGIN . " \n Password : " . $PASS,$DATE,$VERSION);
        		User::where('id',$USER->id)->update([ "last_login" => $DATE ]);
        		return $USER;
        	}
        	else{
        		$this->NEW_REQUEST('Login - Failed',NULL,"Login : " . $LOGIN . " \n Password : " . $PASS,$DATE,$VERSION);
            	return 'failed';
        	}
        }  

        public function SYNC_THETA($DATA,$DATE,$VERSION)
        {
        	$USER_ID = $DATA->user_id;
        	$UPDATE_DATA = $DATA->data;
        	$RETURN_DATA = Array();
	        $SERVER_MONTH =  Date('m');  
	        $NUMBER_OF_QUESTIONS = 11;
	        $CURRENT_DATE_TIME = date('Y-m-d h:i:s');
	        $TIME = time();
	        $DATE = Date('Y-m-d');

	        $this->NEW_REQUEST('Sync',$USER_ID,"Test",$DATE,$VERSION);

	        // UPDATE STATS	        
		        if($UPDATE_DATA != 'No Data')
		        {
		        	foreach ($UPDATE_DATA as $STOREID => $MONTH) {
		        		foreach ($MONTH as $MONTHVALUE => $TYPE) {
		        			foreach ($TYPE as $TYPEVALUE => $DATA) {
			        			if($TYPEVALUE == 'start'){
			        				for ($i=0; $i < $NUMBER_OF_QUESTIONS; $i++) { 
			        					Stats::insert([
			        						"store_id" => $STOREID,
			        						"question_id" => $i+1,
			        						"year" => '2016',
			        						"month" => $MONTHVALUE,
			        						"completed" => 0
			        					]);
			        				}
			        			}else if($TYPEVALUE == 'update'){
			        				foreach ($DATA as $QUESTIONID => $QUESTION_DATA) {
			        					$TEMPUPDATE = Array();
			        					if(!empty($QUESTION_DATA->V1A))
			        					{
			        						$TEMPUPDATE['visit_1_answer'] = $QUESTION_DATA->V1A; 
			        						if($QUESTION_DATA->V1A == 'yes')
			        							$TEMPUPDATE['visit_1_points'] = 1;
			        						else
			        							$TEMPUPDATE['visit_1_points'] = 0;

			        						if(isset($QUESTION_DATA->V1DT) && $QUESTION_DATA->V1DT != '' && !empty($QUESTION_DATA->V1DT)){
				        						$TEMPUPDATE['visit_1_device_time'] = $QUESTION_DATA->V1DT;
				        						$TEMPUPDATE['visit_1_server_time'] = $CURRENT_DATE_TIME;
			        						}
			        						if(isset($QUESTION_DATA->V1C) && $QUESTION_DATA->V1C != '' && !empty($QUESTION_DATA->V1C)){
			        							$TEMPUPDATE['visit_1_comment'] = $QUESTION_DATA->V1C;
			        						}
			        						if(isset($QUESTION_DATA->V1I) && $QUESTION_DATA->V1I != '' && !empty($QUESTION_DATA->V1I)){
			                                    $DATA_CHECK = explode(',',$QUESTION_DATA->V1I);
			                                    $DATA_CHECK = substr($DATA_CHECK[0],0,4);

			                                    if($DATA_CHECK == 'data'){
			                                        $IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'_image_1.jpg';
			                                        $IMAGE_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V1I, $IMAGE_NAME);
			                                    }else{
			                                        $IMAGE_LINK = $QUESTION_DATA->V1I;
			                                    }			        							
			        							$TEMPUPDATE['visit_1_image'] = $IMAGE_LINK;
			        						}			        						
			        					}
			        					if(!empty($QUESTION_DATA->V2A))
			        					{
			        						$TEMPUPDATE['visit_2_answer'] = $QUESTION_DATA->V2A; 
			        						if($QUESTION_DATA->V2A == 'yes')
			        							$TEMPUPDATE['visit_2_points'] = 1;
			        						else
			        							$TEMPUPDATE['visit_2_points'] = 0;

			        						if(isset($QUESTION_DATA->V2DT) && $QUESTION_DATA->V2DT != '' && !empty($QUESTION_DATA->V2DT)){
				        						$TEMPUPDATE['visit_2_device_time'] = $QUESTION_DATA->V2DT;
				        						$TEMPUPDATE['visit_2_server_time'] = $CURRENT_DATE_TIME;
			        						}	
			        						if(isset($QUESTION_DATA->V2C) && $QUESTION_DATA->V2C != '' && !empty($QUESTION_DATA->V2C)){
			        							$TEMPUPDATE['visit_2_comment'] = $QUESTION_DATA->V2C;
			        						}
			        						if(isset($QUESTION_DATA->V2I) && $QUESTION_DATA->V2I != '' && !empty($QUESTION_DATA->V2I)){
			                                    $DATA_CHECK = explode(',',$QUESTION_DATA->V2I);
			                                    $DATA_CHECK = substr($DATA_CHECK[0],0,4);

			                                    if($DATA_CHECK == 'data'){
			                                        $IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'_image_2.jpg';
			                                        $IMAGE_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V2I, $IMAGE_NAME);
			                                    }else{
			                                        $IMAGE_LINK = $QUESTION_DATA->V2I;
			                                    }			        							
			        							$TEMPUPDATE['visit_2_image'] = $IMAGE_LINK;
			        						}			        								        						
			        					}	
			        					if(!empty($QUESTION_DATA->V3A))
			        					{
			        						$TEMPUPDATE['visit_3_answer'] = $QUESTION_DATA->V3A; 
			        						if($QUESTION_DATA->V3A == 'yes')
			        							$TEMPUPDATE['visit_3_points'] = 1;
			        						else
			        							$TEMPUPDATE['visit_3_points'] = 0;

			        						if(isset($QUESTION_DATA->V3DT) && $QUESTION_DATA->V3DT != '' && !empty($QUESTION_DATA->V3DT)){
				        						$TEMPUPDATE['visit_3_device_time'] = $QUESTION_DATA->V3DT;
				        						$TEMPUPDATE['visit_3_server_time'] = $CURRENT_DATE_TIME;
			        						}	
			        						if(isset($QUESTION_DATA->V3C) && $QUESTION_DATA->V3C != '' && !empty($QUESTION_DATA->V3C)){
			        							$TEMPUPDATE['visit_3_comment'] = $QUESTION_DATA->V3C;
			        						}
			        						if(isset($QUESTION_DATA->V3I) && $QUESTION_DATA->V3I != '' && !empty($QUESTION_DATA->V3I)){
			                                    $DATA_CHECK = explode(',',$QUESTION_DATA->V3I);
			                                    $DATA_CHECK = substr($DATA_CHECK[0],0,4);

			                                    if($DATA_CHECK == 'data'){
			                                        $IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'_image_3.jpg';
			                                        $IMAGE_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V3I, $IMAGE_NAME);
			                                    }else{
			                                        $IMAGE_LINK = $QUESTION_DATA->V3I;
			                                    }			        							
			        							$TEMPUPDATE['visit_3_image'] = $IMAGE_LINK;
			        						}			        									        										        						
			        					}
			        					if(!empty($QUESTION_DATA->V4A))
			        					{
			        						$TEMPUPDATE['visit_4_answer'] = $QUESTION_DATA->V4A; 
			        						if($QUESTION_DATA->V4A == 'yes')
			        							$TEMPUPDATE['visit_4_points'] = 1;
			        						else
			        							$TEMPUPDATE['visit_4_points'] = 0;

			        						if(isset($QUESTION_DATA->V4DT) && $QUESTION_DATA->V4DT != '' && !empty($QUESTION_DATA->V4DT)){
				        						$TEMPUPDATE['visit_4_device_time'] = $QUESTION_DATA->V4DT;
				        						$TEMPUPDATE['visit_4_server_time'] = $CURRENT_DATE_TIME;
			        						}	
			        						if(isset($QUESTION_DATA->V4C) && $QUESTION_DATA->V4C != '' && !empty($QUESTION_DATA->V4C)){
			        							$TEMPUPDATE['visit_4_comment'] = $QUESTION_DATA->V4C;
			        						}
			        						if(isset($QUESTION_DATA->V4I) && $QUESTION_DATA->V4I != '' && !empty($QUESTION_DATA->V4I)){
			                                    $DATA_CHECK = explode(',',$QUESTION_DATA->V4I);
			                                    $DATA_CHECK = substr($DATA_CHECK[0],0,4);

			                                    if($DATA_CHECK == 'data'){
			                                        $IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'_image_4.jpg';
			                                        $IMAGE_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V4I, $IMAGE_NAME);
			                                    }else{
			                                        $IMAGE_LINK = $QUESTION_DATA->V4I;
			                                    }			        							
			        							$TEMPUPDATE['visit_4_image'] = $IMAGE_LINK;
			        						}				        								        						
			        					}			        								        							        					
			        					Stats::where("store_id",$STOREID)->where("question_id",$QUESTIONID)->where("year","2016")->where("month",$MONTHVALUE)->update($TEMPUPDATE);
			        				}
			        			}else if($TYPEVALUE == 'completed'){
			        				for ($i=0; $i < $NUMBER_OF_QUESTIONS; $i++) { 
			        					Stats::where("store_id",$STOREID)->where("question_id",$i+1)->where("year","2016")->where("month",$MONTHVALUE)->update(["completed" => 1]);
			        				}
			        			}
		        			}
		        		}
		        	}
		        }    	

        	// CHECK USER
        		$USER_STATUS = User::where('id',$USER_ID)->pluck('status')->toArray();
        		$RETURN_DATA['USER_STATUS'] = $USER_STATUS[0];

        	if($RETURN_DATA['USER_STATUS'] == 'active'){
	        	// GET STORES
	        		$STORES_ARR = Store::where('user_id',$USER_ID)->get();
	        		$STORES = Array();
	        		foreach ($STORES_ARR as $key => $value) {
	        			$STORES[$value->id] = str_replace("'", "''", $value->name);
	        		}

		        	if(count($STORES) > 0){
		        		$RETURN_DATA['LIST_STORES'] = $STORES;
		        	}

		        	foreach ($STORES as $KEY => $VAL) {
		        		$RETURN_DATA['ALL_STORES'][$KEY] = Array();
		        		$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'] = Array();		        		
		        		$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'] = Array();

		        		$CURRENT_STATS = Stats::where('store_id',$KEY)->where('completed','0')->get();
		        		$PAST_STATS = Stats::where('store_id',$KEY)->where('completed','1')->get();

		        		foreach ($CURRENT_STATS as $RKEY => $MONTH) {
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V1A'] = $MONTH->visit_1_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V1DT'] = $MONTH->visit_1_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V1C'] = $MONTH->visit_1_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V1I'] = $MONTH->visit_1_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V2A'] = $MONTH->visit_2_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V2DT'] = $MONTH->visit_2_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V2C'] = $MONTH->visit_2_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V2I'] = $MONTH->visit_2_image;		        			
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V3A'] = $MONTH->visit_3_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V3DT'] = $MONTH->visit_3_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V3C'] = $MONTH->visit_3_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V3I'] = $MONTH->visit_3_image;		        			
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V4A'] = $MONTH->visit_4_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V4DT'] = $MONTH->visit_4_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V4C'] = $MONTH->visit_4_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V4I'] = $MONTH->visit_4_image;		        			
		        		}

		        		foreach ($PAST_STATS as $RKEY => $MONTH) {
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V1A'] = $MONTH->visit_1_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V1C'] = $MONTH->visit_1_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V1I'] = $MONTH->visit_1_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V2A'] = $MONTH->visit_2_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V2C'] = $MONTH->visit_2_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V2I'] = $MONTH->visit_2_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V3A'] = $MONTH->visit_3_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V3C'] = $MONTH->visit_3_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V3I'] = $MONTH->visit_3_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V4A'] = $MONTH->visit_4_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V4C'] = $MONTH->visit_4_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V4I'] = $MONTH->visit_4_image;
		        		}	

		        		$BIGGEST_MONTH = Stats::where('store_id',$KEY)->where('completed','1')->max('month');
		        		if(count($RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS']) < 1){
		        			if($SERVER_MONTH > $BIGGEST_MONTH){
		        				$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'start';
		        			}else{
		        				$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'completed';
		        			}		        			
		        		}else{
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'continue';
		        		}	        		
		        	}

		       	// WORLD SCORE 
	        		$DB_WORLD = DB::select(DB::raw("SELECT `question_id`,SUM(`visit_1_points` + `visit_2_points` + `visit_3_points` + `visit_4_points`) as 'Points' FROM `stats` WHERE `completed` = '1' AND `stats`.`deleted_at` IS NULL GROUP BY `question_id`"));
	        		$QUESTIONS = Question::all();
	        		$RETURN_DATA['WORLD_SCORE'] = Array();
	        		foreach ($QUESTIONS as $QKEY => $QUESTION) 
	        		{
	        			foreach ($DB_WORLD as $WKEY => $VALUE) 
	        			{
	        				if($QUESTION->id == $VALUE->question_id)
	        				{
	        					$RETURN_DATA['WORLD_SCORE'][$QUESTION->id] = $VALUE->Points;
	        				}
	        			}

	        			if(!isset($RETURN_DATA['WORLD_SCORE'][$QUESTION->id])){
	        				$RETURN_DATA['WORLD_SCORE'][$QUESTION->id] = '0';
	        			}
	        		}
        	}
        	return $RETURN_DATA; 
        }

        public function SYNC($DATA,$DATE,$VERSION)
        {
        	$USER_ID = $DATA->user_id;
        	$INCOMING_DATA = $DATA->data;
        	$RETURN_DATA = Array();
	        $SERVER_MONTH =  Date('m');  
	        $NUMBER_OF_QUESTIONS = 13;
	        $CURRENT_DATE_TIME = date('Y-m-d h:i:s');
	        $TIME = time();
	        $DATE = Date('Y-m-d');

	        $this->NEW_REQUEST('Sync',$USER_ID,"Test",$DATE,$VERSION);

	        // UPDATE STATS	        
		        if($INCOMING_DATA != 'No Data')
		        {
		        	foreach ($INCOMING_DATA as $DATA_TYPE => $UPDATE_DATA) {
		        		if($DATA_TYPE === 'DATA'){
				        	foreach ($UPDATE_DATA as $STOREID => $MONTH) {
				        		foreach ($MONTH as $MONTHVALUE => $TYPE) {
				        			foreach ($TYPE as $TYPEVALUE => $DATA) {
					        			if($TYPEVALUE == 'start'){
					        				for ($i=0; $i < $NUMBER_OF_QUESTIONS; $i++) { 
					        					Stats::insert([
					        						"store_id" => $STOREID,
					        						"question_id" => $i+1,
					        						"year" => '2016',
					        						"month" => $MONTHVALUE,
					        						"completed" => 0
					        					]);
					        				}
					        			}else if($TYPEVALUE == 'update'){
					        				foreach ($DATA as $QUESTIONID => $QUESTION_DATA) {
					        					$TEMPUPDATE = Array();
					        					if(!empty($QUESTION_DATA->V1A))
					        					{
					        						$TEMPUPDATE['visit_1_answer'] = $QUESTION_DATA->V1A; 
					        						if($QUESTION_DATA->V1A == 'yes')
					        							$TEMPUPDATE['visit_1_points'] = 1;
					        						else
					        							$TEMPUPDATE['visit_1_points'] = 0;

					        						if(isset($QUESTION_DATA->V1DT) && $QUESTION_DATA->V1DT != '' && !empty($QUESTION_DATA->V1DT)){
						        						$TEMPUPDATE['visit_1_device_time'] = $QUESTION_DATA->V1DT;
						        						$TEMPUPDATE['visit_1_server_time'] = $CURRENT_DATE_TIME;
					        						}
					        						if(isset($QUESTION_DATA->V1C) && $QUESTION_DATA->V1C != '' && !empty($QUESTION_DATA->V1C)){
					        							$TEMPUPDATE['visit_1_comment'] = $QUESTION_DATA->V1C;
					        						}
													if(isset($QUESTION_DATA->V1I) && $QUESTION_DATA->V1I != '' && !empty($QUESTION_DATA->V1I)){
														$IMAGE_COUNT = explode('|',$QUESTION_DATA->V1I);
														$COUNTER = 0;
														$IMAGE_LINK = '';
														foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
															$COUNTER++;
															$DATA_CHECK = explode(',',$IMAGE_DATA);
															$DATA_CHECK = substr($DATA_CHECK[0],0,4);

															if($DATA_CHECK == 'data'){
																$IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'V1'.'_image_'.$COUNTER.'.jpg';
																$IMAGE_TMP_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V1I, $IMAGE_NAME);
															}else{
																$IMAGE_TMP_LINK = $QUESTION_DATA->V1I;
															}
								
															if($IMAGE_LINK == '')
																$IMAGE_LINK = $IMAGE_TMP_LINK;
															else
																$IMAGE_LINK = $IMAGE_LINK . '|' . $IMAGE_TMP_LINK;
														}
														$TEMPUPDATE['visit_1_image'] = $IMAGE_LINK;
					        						}	
					        					}
					        					if(!empty($QUESTION_DATA->V2A))
					        					{
					        						$TEMPUPDATE['visit_2_answer'] = $QUESTION_DATA->V2A; 
					        						if($QUESTION_DATA->V2A == 'yes')
					        							$TEMPUPDATE['visit_2_points'] = 1;
					        						else
					        							$TEMPUPDATE['visit_2_points'] = 0;

					        						if(isset($QUESTION_DATA->V2DT) && $QUESTION_DATA->V2DT != '' && !empty($QUESTION_DATA->V2DT)){
						        						$TEMPUPDATE['visit_2_device_time'] = $QUESTION_DATA->V2DT;
						        						$TEMPUPDATE['visit_2_server_time'] = $CURRENT_DATE_TIME;
					        						}	
					        						if(isset($QUESTION_DATA->V2C) && $QUESTION_DATA->V2C != '' && !empty($QUESTION_DATA->V2C)){
					        							$TEMPUPDATE['visit_2_comment'] = $QUESTION_DATA->V2C;
					        						}
					        						if(isset($QUESTION_DATA->V2I) && $QUESTION_DATA->V2I != '' && !empty($QUESTION_DATA->V2I)){
														$IMAGE_COUNT = explode('|',$QUESTION_DATA->V2I);
														$COUNTER = 0;
														$IMAGE_LINK = '';
														foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
															$COUNTER++;
															$DATA_CHECK = explode(',',$IMAGE_DATA);
															$DATA_CHECK = substr($DATA_CHECK[0],0,4);

															if($DATA_CHECK == 'data'){
																$IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'V2'.'_image_'.$COUNTER.'.jpg';
																$IMAGE_TMP_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V2I, $IMAGE_NAME);
															}else{
																$IMAGE_TMP_LINK = $QUESTION_DATA->V2I;
															}
								
															if($IMAGE_LINK == '')
																$IMAGE_LINK = $IMAGE_TMP_LINK;
															else
																$IMAGE_LINK = $IMAGE_LINK . '|' . $IMAGE_TMP_LINK;
														}
														$TEMPUPDATE['visit_2_image'] = $IMAGE_LINK;
					        						}			        								        						
					        					}	
					        					if(!empty($QUESTION_DATA->V3A))
					        					{
					        						$TEMPUPDATE['visit_3_answer'] = $QUESTION_DATA->V3A; 
					        						if($QUESTION_DATA->V3A == 'yes')
					        							$TEMPUPDATE['visit_3_points'] = 1;
					        						else
					        							$TEMPUPDATE['visit_3_points'] = 0;

					        						if(isset($QUESTION_DATA->V3DT) && $QUESTION_DATA->V3DT != '' && !empty($QUESTION_DATA->V3DT)){
						        						$TEMPUPDATE['visit_3_device_time'] = $QUESTION_DATA->V3DT;
						        						$TEMPUPDATE['visit_3_server_time'] = $CURRENT_DATE_TIME;
					        						}	
					        						if(isset($QUESTION_DATA->V3C) && $QUESTION_DATA->V3C != '' && !empty($QUESTION_DATA->V3C)){
					        							$TEMPUPDATE['visit_3_comment'] = $QUESTION_DATA->V3C;
					        						}
					        						if(isset($QUESTION_DATA->V3I) && $QUESTION_DATA->V3I != '' && !empty($QUESTION_DATA->V3I)){
														$IMAGE_COUNT = explode('|',$QUESTION_DATA->V3I);
														$COUNTER = 0;
														$IMAGE_LINK = '';
														foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
															$COUNTER++;
															$DATA_CHECK = explode(',',$IMAGE_DATA);
															$DATA_CHECK = substr($DATA_CHECK[0],0,4);

															if($DATA_CHECK == 'data'){
																$IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'V3'.'_image_'.$COUNTER.'.jpg';
																$IMAGE_TMP_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V3I, $IMAGE_NAME);
															}else{
																$IMAGE_TMP_LINK = $QUESTION_DATA->V3I;
															}
								
															if($IMAGE_LINK == '')
																$IMAGE_LINK = $IMAGE_TMP_LINK;
															else
																$IMAGE_LINK = $IMAGE_LINK . '|' . $IMAGE_TMP_LINK;
														}
														$TEMPUPDATE['visit_3_image'] = $IMAGE_LINK;
					        						}			        									        										        						
					        					}
					        					if(!empty($QUESTION_DATA->V4A))
					        					{
					        						$TEMPUPDATE['visit_4_answer'] = $QUESTION_DATA->V4A; 
					        						if($QUESTION_DATA->V4A == 'yes')
					        							$TEMPUPDATE['visit_4_points'] = 1;
					        						else
					        							$TEMPUPDATE['visit_4_points'] = 0;

					        						if(isset($QUESTION_DATA->V4DT) && $QUESTION_DATA->V4DT != '' && !empty($QUESTION_DATA->V4DT)){
						        						$TEMPUPDATE['visit_4_device_time'] = $QUESTION_DATA->V4DT;
						        						$TEMPUPDATE['visit_4_server_time'] = $CURRENT_DATE_TIME;
					        						}	
					        						if(isset($QUESTION_DATA->V4C) && $QUESTION_DATA->V4C != '' && !empty($QUESTION_DATA->V4C)){
					        							$TEMPUPDATE['visit_4_comment'] = $QUESTION_DATA->V4C;
					        						}
					        						if(isset($QUESTION_DATA->V4I) && $QUESTION_DATA->V4I != '' && !empty($QUESTION_DATA->V4I)){
														$IMAGE_COUNT = explode('|',$QUESTION_DATA->V4I);
														$COUNTER = 0;
														$IMAGE_LINK = '';
														foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
															$COUNTER++;
															$DATA_CHECK = explode(',',$IMAGE_DATA);
															$DATA_CHECK = substr($DATA_CHECK[0],0,4);

															if($DATA_CHECK == 'data'){
																$IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'V4'.'_image_'.$COUNTER.'.jpg';
																$IMAGE_TMP_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V4I, $IMAGE_NAME);
															}else{
																$IMAGE_TMP_LINK = $QUESTION_DATA->V1I;
															}
								
															if($IMAGE_LINK == '')
																$IMAGE_LINK = $IMAGE_TMP_LINK;
															else
																$IMAGE_LINK = $IMAGE_LINK . '|' . $IMAGE_TMP_LINK;
														}
														$TEMPUPDATE['visit_4_image'] = $IMAGE_LINK;
					        						}				        								        						
					        					}			        								        							        					
					        					Stats::where("store_id",$STOREID)->where("question_id",$QUESTIONID)->where("year","2016")->where("month",$MONTHVALUE)->update($TEMPUPDATE);
					        				}
					        			}else if($TYPEVALUE == 'completed'){
					        				for ($i=0; $i < $NUMBER_OF_QUESTIONS; $i++) { 
					        					Stats::where("store_id",$STOREID)->where("question_id",$i+1)->where("year","2016")->where("month",$MONTHVALUE)->update(["completed" => 1]);
					        				}
					        			}
				        			}
				        		}
				        	}
				        }

				        if($DATA_TYPE === 'POSITION'){
				        	foreach ($UPDATE_DATA as $STORE_ID => $POSITIONS) {
				        		Store::where("id",$STORE_ID)->update([
				        			"latitude" 	=> $POSITIONS->LAT,
				        			"longitude" => $POSITIONS->LONG
				        		]);
				        	}
				        }

				        if($DATA_TYPE === 'COMPETITOR'){
				        	foreach ($UPDATE_DATA as $STORE_ID => $DATETIME_DATA) {
				        		foreach ($DATETIME_DATA as $DATETIME => $COMPETITORS) {
				        			foreach ($COMPETITORS as $COMPETITOR_ID => $COMPETITOR_DATA) {
				        				if($COMPETITOR_DATA->comment != '' || $COMPETITOR_DATA->images != ''){
				        					$IMAGESTRING = '';
											if(isset($COMPETITOR_DATA->images) && $COMPETITOR_DATA->images != '' && !empty($COMPETITOR_DATA->images)){
												$IMAGE_COUNT = explode('|',$COMPETITOR_DATA->images);
												$COUNTER = 0;
												foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
													$COUNTER++;
													$DATA_CHECK = explode(',',$IMAGE_DATA);
													$DATA_CHECK = substr($DATA_CHECK[0],0,4);

													$IMAGE_NAME = $STORE_ID.$COMPETITOR_ID.$TIME.'_capture_'.$COUNTER.'.jpg';

													// if($IMAGESTRING == '')			        							
													// 	$IMAGESTRING = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$STORE_ID.'/'.$this->TO_JPEG2( $IMAGE_DATA, $IMAGE_NAME, $STORE_ID, 'photo' );
													// else
													// 	$IMAGESTRING = $IMAGESTRING . '|https://s3-eu-west-1.amazonaws.com/itap-photo-store-reunion/photo/'.$STORE_ID.'/'.$this->TO_JPEG2( $IMAGE_DATA, $IMAGE_NAME, $STORE_ID, 'photo' );													

													if($IMAGESTRING == '')
														$IMAGESTRING = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($IMAGE_DATA, $IMAGE_NAME);
													else
														$IMAGESTRING = $IMAGESTRING . '|https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($IMAGE_DATA, $IMAGE_NAME);
												}
			        						}else{
			        							$IMAGESTRING = '';
			        						}			        						

					        				CompetitorActivity::insert([
					        					"competitor_id" => $COMPETITOR_ID,
					        					"store_id" 		=> $STORE_ID,
					        					"comment"    	=> $COMPETITOR_DATA->comment,
					        					"images"        => $IMAGESTRING
					        				]);
				        				}
				        			}
				        		}
				        	}
				        }
		        	}
		        }    	

        	// CHECK USER
        		$USER_STATUS = User::where('id',$USER_ID)->pluck('status')->toArray();
        		$RETURN_DATA['USER_STATUS'] = $USER_STATUS[0];

        	if($RETURN_DATA['USER_STATUS'] == 'active'){
	        	// GET STORES
	        		$STORES_ARR = Store::where('user_id',$USER_ID)->get();
	        		$STORES = Array();
	        		foreach ($STORES_ARR as $key => $value) {
	        			$STORES[$value->id] = str_replace("'", "''", $value->name);
	        		}

		        	if(count($STORES) > 0){
		        		$RETURN_DATA['LIST_STORES'] = $STORES;
		        	}

		        	foreach ($STORES as $KEY => $VAL) {
		        		$RETURN_DATA['ALL_STORES'][$KEY] = Array();
		        		$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'] = Array();		        		
		        		$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'] = Array();
		        		$RETURN_DATA['ALL_STORES'][$KEY]['POSITION'] = Array();

		        		$CURRENT_STATS = Stats::where('store_id',$KEY)->where('completed','0')->get();
		        		$PAST_STATS = Stats::where('store_id',$KEY)->where('completed','1')->get();
		        		$STORE_DETAILS = Store::find($KEY);

		        		$RETURN_DATA['ALL_STORES'][$KEY]['POSITION']['LAT'] = $STORE_DETAILS->latitude ? $STORE_DETAILS->latitude : 'empty' ;
		        		$RETURN_DATA['ALL_STORES'][$KEY]['POSITION']['LONG'] = $STORE_DETAILS->longitude ? $STORE_DETAILS->longitude : 'empty' ;

		        		foreach ($CURRENT_STATS as $RKEY => $MONTH) {
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V1A'] = $MONTH->visit_1_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V1DT'] = $MONTH->visit_1_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V1C'] = $MONTH->visit_1_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V1I'] = $MONTH->visit_1_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V2A'] = $MONTH->visit_2_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V2DT'] = $MONTH->visit_2_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V2C'] = $MONTH->visit_2_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V2I'] = $MONTH->visit_2_image;		        			
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V3A'] = $MONTH->visit_3_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V3DT'] = $MONTH->visit_3_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V3C'] = $MONTH->visit_3_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V3I'] = $MONTH->visit_3_image;		        			
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V4A'] = $MONTH->visit_4_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V4DT'] = $MONTH->visit_4_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V4C'] = $MONTH->visit_4_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$MONTH->month][$MONTH->question_id]['V4I'] = $MONTH->visit_4_image;		        			
		        		}

		        		foreach ($PAST_STATS as $RKEY => $MONTH) {
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V1A'] = $MONTH->visit_1_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V1C'] = $MONTH->visit_1_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V1I'] = $MONTH->visit_1_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V2A'] = $MONTH->visit_2_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V2C'] = $MONTH->visit_2_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V2I'] = $MONTH->visit_2_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V3A'] = $MONTH->visit_3_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V3C'] = $MONTH->visit_3_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V3I'] = $MONTH->visit_3_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V4A'] = $MONTH->visit_4_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V4C'] = $MONTH->visit_4_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$MONTH->month][$MONTH->question_id]['V4I'] = $MONTH->visit_4_image;
		        		}	

		        		$BIGGEST_MONTH = Stats::where('store_id',$KEY)->where('completed','1')->max('month');
		        		if(count($RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS']) < 1){
		        			if($SERVER_MONTH > $BIGGEST_MONTH){
		        				$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'Démarrer';
		        			}else{
		        				$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'Terminé';
		        			}		        			
		        		}else{
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'Continuer';
		        		}	        		
		        	}

		       	// WORLD SCORE 
	        		$DB_WORLD = DB::select(DB::raw("SELECT `question_id`,SUM(`visit_1_points` + `visit_2_points` + `visit_3_points` + `visit_4_points`) as 'Points' FROM `stats` WHERE `completed` = '1' AND `stats`.`deleted_at` IS NULL GROUP BY `question_id`"));
	        		$QUESTIONS = Question::all();
	        		$RETURN_DATA['WORLD_SCORE'] = Array();
	        		foreach ($QUESTIONS as $QKEY => $QUESTION) 
	        		{
	        			foreach ($DB_WORLD as $WKEY => $VALUE) 
	        			{
	        				if($QUESTION->id == $VALUE->question_id)
	        				{
	        					$RETURN_DATA['WORLD_SCORE'][$QUESTION->id] = $VALUE->Points;
	        				}
	        			}

	        			if(!isset($RETURN_DATA['WORLD_SCORE'][$QUESTION->id])){
	        				$RETURN_DATA['WORLD_SCORE'][$QUESTION->id] = '0';
	        			}
	        		}

	        	// COMPETITORS
	        		$COMPETITORS = Competitor::all();
	        		$RETURN_DATA['COMPETITORS'] = Array();
	        		foreach ($COMPETITORS as $CKEY => $COMPETITOR) {
	        			$RETURN_DATA['COMPETITORS'][$COMPETITOR->id] = $COMPETITOR->name;
	        		}
        	}
        	return $RETURN_DATA; 
        }        

        public function SYNC_NEW($DATA,$DATE,$VERSION)
        {
        	$USER_ID = $DATA->user_id;
        	$INCOMING_DATA = $DATA->data;
        	$RETURN_DATA = Array();
	        $SERVER_MONTH =  Date('m'); 
	        $SERVER_YEAR =  Date('Y');  
	        $NUMBER_OF_QUESTIONS = 13;
	        $CURRENT_DATE_TIME = date('Y-m-d h:i:s');
	        $TIME = time();
	        $DATE = Date('Y-m-d');

	        $this->NEW_REQUEST('Sync',$USER_ID,"Test",$DATE,$VERSION);

	        // UPDATE STATS	        
		        if($INCOMING_DATA != 'No Data')
		        {
		        	foreach ($INCOMING_DATA as $DATA_TYPE => $UPDATE_DATA) {
		        		if($DATA_TYPE === 'DATA'){
				        	foreach ($UPDATE_DATA as $STOREID => $YEAR) {
				        		foreach ($YEAR as $YEARVALUE => $MONTH) {
					        		foreach ($MONTH as $MONTHVALUE => $TYPE) {
					        			foreach ($TYPE as $TYPEVALUE => $DATA) {
						        			if($TYPEVALUE == 'start'){
						        				for ($i=0; $i < $NUMBER_OF_QUESTIONS; $i++) { 
						        					$EXIST = Stats::where("store_id",$STOREID)->where("question_id",$i+1)->where("year",$YEARVALUE)->where("month",$MONTHVALUE)->first();
						        					if(!isset($EXIST)){
														Stats::insert([
							        						"store_id" => $STOREID,
							        						"question_id" => $i+1,
							        						"year" => $YEARVALUE,
							        						"month" => $MONTHVALUE,
							        						"completed" => 0
							        					]);						        						
						        					}						        					
						        				}
						        			}else if($TYPEVALUE == 'update'){
						        				foreach ($DATA as $QUESTIONID => $QUESTION_DATA) {
						        					$TEMPUPDATE = Array();
						        					if(!empty($QUESTION_DATA->V1A))
						        					{
						        						$TEMPUPDATE['visit_1_answer'] = $QUESTION_DATA->V1A; 
						        						if($QUESTION_DATA->V1A == 'yes')
						        							$TEMPUPDATE['visit_1_points'] = 1;
						        						else
						        							$TEMPUPDATE['visit_1_points'] = 0;

						        						if(isset($QUESTION_DATA->V1DT) && $QUESTION_DATA->V1DT != '' && !empty($QUESTION_DATA->V1DT)){
							        						$TEMPUPDATE['visit_1_device_time'] = $QUESTION_DATA->V1DT;
							        						$TEMPUPDATE['visit_1_server_time'] = $CURRENT_DATE_TIME;
						        						}
						        						if(isset($QUESTION_DATA->V1C) && $QUESTION_DATA->V1C != '' && !empty($QUESTION_DATA->V1C)){
						        							$TEMPUPDATE['visit_1_comment'] = $QUESTION_DATA->V1C;
						        						}
														if(isset($QUESTION_DATA->V1I) && $QUESTION_DATA->V1I != '' && !empty($QUESTION_DATA->V1I)){
															$IMAGE_COUNT = explode('|',$QUESTION_DATA->V1I);
															$COUNTER = 0;
															$IMAGE_LINK = '';
															foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
																$COUNTER++;
																$DATA_CHECK = explode(',',$IMAGE_DATA);
																$DATA_CHECK = substr($DATA_CHECK[0],0,4);

																if($DATA_CHECK == 'data'){
																	$IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'V1'.'_image_'.$COUNTER.'.jpg';
																	$IMAGE_TMP_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V1I, $IMAGE_NAME);
																}else{
																	$IMAGE_TMP_LINK = $QUESTION_DATA->V1I;
																}
									
																if($IMAGE_LINK == '')
																	$IMAGE_LINK = $IMAGE_TMP_LINK;
																else
																	$IMAGE_LINK = $IMAGE_LINK . '|' . $IMAGE_TMP_LINK;
															}
															$TEMPUPDATE['visit_1_image'] = $IMAGE_LINK;
						        						}	
						        					}
						        					if(!empty($QUESTION_DATA->V2A))
						        					{
						        						$TEMPUPDATE['visit_2_answer'] = $QUESTION_DATA->V2A; 
						        						if($QUESTION_DATA->V2A == 'yes')
						        							$TEMPUPDATE['visit_2_points'] = 1;
						        						else
						        							$TEMPUPDATE['visit_2_points'] = 0;

						        						if(isset($QUESTION_DATA->V2DT) && $QUESTION_DATA->V2DT != '' && !empty($QUESTION_DATA->V2DT)){
							        						$TEMPUPDATE['visit_2_device_time'] = $QUESTION_DATA->V2DT;
							        						$TEMPUPDATE['visit_2_server_time'] = $CURRENT_DATE_TIME;
						        						}	
						        						if(isset($QUESTION_DATA->V2C) && $QUESTION_DATA->V2C != '' && !empty($QUESTION_DATA->V2C)){
						        							$TEMPUPDATE['visit_2_comment'] = $QUESTION_DATA->V2C;
						        						}
						        						if(isset($QUESTION_DATA->V2I) && $QUESTION_DATA->V2I != '' && !empty($QUESTION_DATA->V2I)){
															$IMAGE_COUNT = explode('|',$QUESTION_DATA->V2I);
															$COUNTER = 0;
															$IMAGE_LINK = '';
															foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
																$COUNTER++;
																$DATA_CHECK = explode(',',$IMAGE_DATA);
																$DATA_CHECK = substr($DATA_CHECK[0],0,4);

																if($DATA_CHECK == 'data'){
																	$IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'V2'.'_image_'.$COUNTER.'.jpg';
																	$IMAGE_TMP_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V2I, $IMAGE_NAME);
																}else{
																	$IMAGE_TMP_LINK = $QUESTION_DATA->V2I;
																}
									
																if($IMAGE_LINK == '')
																	$IMAGE_LINK = $IMAGE_TMP_LINK;
																else
																	$IMAGE_LINK = $IMAGE_LINK . '|' . $IMAGE_TMP_LINK;
															}
															$TEMPUPDATE['visit_2_image'] = $IMAGE_LINK;
						        						}			        								        						
						        					}	
						        					if(!empty($QUESTION_DATA->V3A))
						        					{
						        						$TEMPUPDATE['visit_3_answer'] = $QUESTION_DATA->V3A; 
						        						if($QUESTION_DATA->V3A == 'yes')
						        							$TEMPUPDATE['visit_3_points'] = 1;
						        						else
						        							$TEMPUPDATE['visit_3_points'] = 0;

						        						if(isset($QUESTION_DATA->V3DT) && $QUESTION_DATA->V3DT != '' && !empty($QUESTION_DATA->V3DT)){
							        						$TEMPUPDATE['visit_3_device_time'] = $QUESTION_DATA->V3DT;
							        						$TEMPUPDATE['visit_3_server_time'] = $CURRENT_DATE_TIME;
						        						}	
						        						if(isset($QUESTION_DATA->V3C) && $QUESTION_DATA->V3C != '' && !empty($QUESTION_DATA->V3C)){
						        							$TEMPUPDATE['visit_3_comment'] = $QUESTION_DATA->V3C;
						        						}
						        						if(isset($QUESTION_DATA->V3I) && $QUESTION_DATA->V3I != '' && !empty($QUESTION_DATA->V3I)){
															$IMAGE_COUNT = explode('|',$QUESTION_DATA->V3I);
															$COUNTER = 0;
															$IMAGE_LINK = '';
															foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
																$COUNTER++;
																$DATA_CHECK = explode(',',$IMAGE_DATA);
																$DATA_CHECK = substr($DATA_CHECK[0],0,4);

																if($DATA_CHECK == 'data'){
																	$IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'V3'.'_image_'.$COUNTER.'.jpg';
																	$IMAGE_TMP_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V3I, $IMAGE_NAME);
																}else{
																	$IMAGE_TMP_LINK = $QUESTION_DATA->V3I;
																}
									
																if($IMAGE_LINK == '')
																	$IMAGE_LINK = $IMAGE_TMP_LINK;
																else
																	$IMAGE_LINK = $IMAGE_LINK . '|' . $IMAGE_TMP_LINK;
															}
															$TEMPUPDATE['visit_3_image'] = $IMAGE_LINK;
						        						}			        									        										        						
						        					}
						        					if(!empty($QUESTION_DATA->V4A))
						        					{
						        						$TEMPUPDATE['visit_4_answer'] = $QUESTION_DATA->V4A; 
						        						if($QUESTION_DATA->V4A == 'yes')
						        							$TEMPUPDATE['visit_4_points'] = 1;
						        						else
						        							$TEMPUPDATE['visit_4_points'] = 0;

						        						if(isset($QUESTION_DATA->V4DT) && $QUESTION_DATA->V4DT != '' && !empty($QUESTION_DATA->V4DT)){
							        						$TEMPUPDATE['visit_4_device_time'] = $QUESTION_DATA->V4DT;
							        						$TEMPUPDATE['visit_4_server_time'] = $CURRENT_DATE_TIME;
						        						}	
						        						if(isset($QUESTION_DATA->V4C) && $QUESTION_DATA->V4C != '' && !empty($QUESTION_DATA->V4C)){
						        							$TEMPUPDATE['visit_4_comment'] = $QUESTION_DATA->V4C;
						        						}
						        						if(isset($QUESTION_DATA->V4I) && $QUESTION_DATA->V4I != '' && !empty($QUESTION_DATA->V4I)){
															$IMAGE_COUNT = explode('|',$QUESTION_DATA->V4I);
															$COUNTER = 0;
															$IMAGE_LINK = '';
															foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
																$COUNTER++;
																$DATA_CHECK = explode(',',$IMAGE_DATA);
																$DATA_CHECK = substr($DATA_CHECK[0],0,4);

																if($DATA_CHECK == 'data'){
																	$IMAGE_NAME = $STOREID.$DATE.$MONTHVALUE.$QUESTIONID.$TIME.'V4'.'_image_'.$COUNTER.'.jpg';
																	$IMAGE_TMP_LINK = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($QUESTION_DATA->V4I, $IMAGE_NAME);
																}else{
																	$IMAGE_TMP_LINK = $QUESTION_DATA->V1I;
																}
									
																if($IMAGE_LINK == '')
																	$IMAGE_LINK = $IMAGE_TMP_LINK;
																else
																	$IMAGE_LINK = $IMAGE_LINK . '|' . $IMAGE_TMP_LINK;
															}
															$TEMPUPDATE['visit_4_image'] = $IMAGE_LINK;
						        						}				        								        						
						        					}			        								        							        					
						        					Stats::where("store_id",$STOREID)->where("question_id",$QUESTIONID)->where("year",$YEARVALUE)->where("month",$MONTHVALUE)->update($TEMPUPDATE);
						        				}
						        			}else if($TYPEVALUE == 'completed'){
						        				for ($i=0; $i < $NUMBER_OF_QUESTIONS; $i++) { 
						        					Stats::where("store_id",$STOREID)->where("question_id",$i+1)->where("year",$YEARVALUE)->where("month",$MONTHVALUE)->update(["completed" => 1]);
						        				}
						        			}
					        			}
					        		}
				        		}
				        	}
				        }

				        if($DATA_TYPE === 'POSITION'){
				        	foreach ($UPDATE_DATA as $STORE_ID => $POSITIONS) {
				        		Store::where("id",$STORE_ID)->update([
				        			"latitude" 	=> $POSITIONS->LAT,
				        			"longitude" => $POSITIONS->LONG
				        		]);
				        	}
				        }

				        if($DATA_TYPE === 'COMPETITOR'){
				        	foreach ($UPDATE_DATA as $STORE_ID => $DATETIME_DATA) {
				        		foreach ($DATETIME_DATA as $DATETIME => $COMPETITORS) {
				        			foreach ($COMPETITORS as $COMPETITOR_ID => $COMPETITOR_DATA) {
				        				if($COMPETITOR_DATA->comment != '' || $COMPETITOR_DATA->images != ''){
				        					$IMAGESTRING = '';
											if(isset($COMPETITOR_DATA->images) && $COMPETITOR_DATA->images != '' && !empty($COMPETITOR_DATA->images)){
												$IMAGE_COUNT = explode('|',$COMPETITOR_DATA->images);
												$COUNTER = 0;
												foreach ($IMAGE_COUNT as $IMAGE_KEY => $IMAGE_DATA) {
													$COUNTER++;
													$DATA_CHECK = explode(',',$IMAGE_DATA);
													$DATA_CHECK = substr($DATA_CHECK[0],0,4);

													$IMAGE_NAME = $STORE_ID.$COMPETITOR_ID.$TIME.'_capture_'.$COUNTER.'.jpg';

													// if($IMAGESTRING == '')			        							
													// 	$IMAGESTRING = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store-reunion/photo/'.$STORE_ID.'/'.$this->TO_JPEG2( $IMAGE_DATA, $IMAGE_NAME, $STORE_ID, 'photo' );
													// else
													// 	$IMAGESTRING = ' | https://s3-eu-west-1.amazonaws.com/itap-photo-store-reunion/photo/'.$STORE_ID.'/'.$this->TO_JPEG2( $IMAGE_DATA, $IMAGE_NAME, $STORE_ID, 'photo' );													

													if($IMAGESTRING == '')
														$IMAGESTRING = 'https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($IMAGE_DATA, $IMAGE_NAME);
													else
														$IMAGESTRING = $IMAGESTRING . '|https://pmi-reunion-app.optimalonline.co.za/images/'.$this->TO_JPEG($IMAGE_DATA, $IMAGE_NAME);
												}
			        						}else{
			        							$IMAGESTRING = '';
			        						}			        						

					        				CompetitorActivity::insert([
					        					"competitor_id" => $COMPETITOR_ID,
					        					"store_id" 		=> $STORE_ID,
					        					"comment"    	=> $COMPETITOR_DATA->comment,
					        					"images"        => $IMAGESTRING
					        				]);
				        				}
				        			}
				        		}
				        	}
				        }
		        	}
		        }    	

        	// CHECK USER
        		$USER_STATUS = User::where('id',$USER_ID)->pluck('status')->toArray();
        		$RETURN_DATA['USER_STATUS'] = $USER_STATUS[0];

        	if($RETURN_DATA['USER_STATUS'] == 'active'){
	        	// GET STORES
	        		$STORES_ARR = Store::where('user_id',$USER_ID)->get();
	        		$STORES = Array();
	        		foreach ($STORES_ARR as $key => $value) {
	        			$STORES[$value->id] = str_replace("'", "''", $value->name);
	        		}

		        	if(count($STORES) > 0){
		        		$RETURN_DATA['LIST_STORES'] = $STORES;
		        	}

		        	foreach ($STORES as $KEY => $VAL) {
		        		$RETURN_DATA['ALL_STORES'][$KEY] = Array();
		        		$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'] = Array();		        		
		        		$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'] = Array();
		        		$RETURN_DATA['ALL_STORES'][$KEY]['POSITION'] = Array();

		        		$CURRENT_STATS = Stats::where('store_id',$KEY)->where('completed','0')->get();
		        		$PAST_STATS = Stats::where('store_id',$KEY)->where('completed','1')->get();
		        		$STORE_DETAILS = Store::find($KEY);

		        		$RETURN_DATA['ALL_STORES'][$KEY]['POSITION']['LAT'] = $STORE_DETAILS->latitude ? $STORE_DETAILS->latitude : 'empty' ;
		        		$RETURN_DATA['ALL_STORES'][$KEY]['POSITION']['LONG'] = $STORE_DETAILS->longitude ? $STORE_DETAILS->longitude : 'empty' ;

		        		foreach ($CURRENT_STATS as $STATS_KEY => $STATS_DATA) {
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V1A'] = $STATS_DATA->visit_1_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V1DT'] = $STATS_DATA->visit_1_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V1C'] = $STATS_DATA->visit_1_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V1I'] = $STATS_DATA->visit_1_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V2A'] = $STATS_DATA->visit_2_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V2DT'] = $STATS_DATA->visit_2_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V2C'] = $STATS_DATA->visit_2_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V2I'] = $STATS_DATA->visit_2_image;		        			
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V3A'] = $STATS_DATA->visit_3_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V3DT'] = $STATS_DATA->visit_3_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V3C'] = $STATS_DATA->visit_3_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V3I'] = $STATS_DATA->visit_3_image;		        			
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V4A'] = $STATS_DATA->visit_4_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V4DT'] = $STATS_DATA->visit_4_device_time;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V4C'] = $STATS_DATA->visit_4_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V4I'] = $STATS_DATA->visit_4_image;		        			
		        		}

		        		foreach ($PAST_STATS as $STATS_KEY => $STATS_DATA) {
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V1A'] = $STATS_DATA->visit_1_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V1C'] = $STATS_DATA->visit_1_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V1I'] = $STATS_DATA->visit_1_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V2A'] = $STATS_DATA->visit_2_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V2C'] = $STATS_DATA->visit_2_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V2I'] = $STATS_DATA->visit_2_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V3A'] = $STATS_DATA->visit_3_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V3C'] = $STATS_DATA->visit_3_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V3I'] = $STATS_DATA->visit_3_image;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V4A'] = $STATS_DATA->visit_4_answer;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V4C'] = $STATS_DATA->visit_4_comment;
		        			$RETURN_DATA['ALL_STORES'][$KEY]['PAST_STATS'][$STATS_DATA->year][$STATS_DATA->month][$STATS_DATA->question_id]['V4I'] = $STATS_DATA->visit_4_image;
		        		}	

		        		$BIGGEST_MONTH = Stats::where('store_id',$KEY)->where('completed','1')->select('year','month')->orderBy('year','DESC')->orderBy('month','DESC')->first();
		        		if(count($RETURN_DATA['ALL_STORES'][$KEY]['CURRENT_STATS']) < 1){
		        			if(isset($BIGGEST_MONTH)){
			        			if($SERVER_YEAR > $BIGGEST_MONTH->year){
			        				$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'Démarrer';
			        			}else{
			        				if($SERVER_MONTH > $BIGGEST_MONTH->month){
			        					$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'Démarrer';
			        				}else{
										$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'Terminé';
			        				}		        				
			        			}
		        			}else{
		        				$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'Démarrer';
		        			}
		        		}else{
		        			$RETURN_DATA['ALL_STORES'][$KEY]['CALL_STATUS'] = 'Continuer';
		        		} 	        		
		        	}

		       	// WORLD SCORE 
	        		$DB_WORLD = DB::select(DB::raw("SELECT `question_id`,SUM(`visit_1_points` + `visit_2_points` + `visit_3_points` + `visit_4_points`) as 'Points' FROM `stats` WHERE `completed` = '1' AND `stats`.`deleted_at` IS NULL GROUP BY `question_id`"));
	        		$QUESTIONS = Question::all();
	        		$RETURN_DATA['WORLD_SCORE'] = Array();
	        		foreach ($QUESTIONS as $QKEY => $QUESTION) 
	        		{
	        			foreach ($DB_WORLD as $WKEY => $VALUE) 
	        			{
	        				if($QUESTION->id == $VALUE->question_id)
	        				{
	        					$RETURN_DATA['WORLD_SCORE'][$QUESTION->id] = $VALUE->Points;
	        				}
	        			}

	        			if(!isset($RETURN_DATA['WORLD_SCORE'][$QUESTION->id])){
	        				$RETURN_DATA['WORLD_SCORE'][$QUESTION->id] = '0';
	        			}
	        		}

	        	// COMPETITORS
	        		$COMPETITORS = Competitor::all();
	        		$RETURN_DATA['COMPETITORS'] = Array();
	        		foreach ($COMPETITORS as $CKEY => $COMPETITOR) {
	        			$RETURN_DATA['COMPETITORS'][$COMPETITOR->id] = $COMPETITOR->name;
	        		}
        	}
        	return $RETURN_DATA; 
        }  

        public function test(){
        	$USER_STATUS = User::all();
        	echo '<pre>';
        	var_dump($USER_STATUS);
        	echo '</pre>';
        }

    // FUNCTIONS
        // ADD A NEW REQUEST
            public function NEW_REQUEST($TYPE,$USER,$DATA,$DATE,$VERSION){
                Request::insert([
                    'user_id'      		  => $USER,
                    'app_version'  		  => $VERSION,
                    'request_type'        => $TYPE,
                    'request_data'        => $DATA,
                    'timedate_of_request' => $DATE
                ]);
            } 

         // IMAGE CONVERTER
            function TO_JPEG($BASE_64, $FILE_NAME) {
                $IFP = fopen(base_path() . '/public/images/'.$FILE_NAME, "wb"); 
                $DATA = explode(',', $BASE_64);

                fwrite($IFP, base64_decode($DATA[1])); 
                fclose($IFP); 

                return $FILE_NAME; 
            }  

			function TO_JPEG2($BASE_64, $FILE_NAME, $STORE_ID, $TYPE) {
                $IFP = fopen('/tmp/'.$FILE_NAME, "wb"); 
                $DATA = explode(',', $BASE_64);

                fwrite($IFP, base64_decode($DATA[1])); 
                fclose($IFP); 

				$REMOTE_IMAGE_PATH = $TYPE.'/'.$STORE_ID.'/';

                Storage::disk('itap_photo_store_reunion')->put($REMOTE_IMAGE_PATH.$FILE_NAME, fopen('/tmp/'.$FILE_NAME, 'r+'),'public');
                // unlink('/tmp/'.$FILE_NAME);                

                return $FILE_NAME; 
            }                                                                       
}
