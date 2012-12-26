<?php

class ProgramController extends Controller
{

     
	function actionPublish(){
        header('Content-type: application/json');
	    if(!Yii::app()->request->isPostRequest){   
	   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
	   		 return ;
	   	}
	    if(!IjoyPlusServiceUtils::validateAPPKey()){
  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
		   return ;
		}
		if(IjoyPlusServiceUtils::validateUserID()){
			IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
			return ;
		}
		$prod_id= Yii::app()->request->getParam("prod_id");
		if( (!isset($prod_id)) || is_null($prod_id)  ){
			IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
			return;
		}
		$owner_id=Yii::app()->user->id;
		$transaction = Yii::app()->db->beginTransaction();
		try {	
	      $model= Program::model()->findByPk($prod_id);
		  if($model !==null){
		  	if(isset($model->publish_owner_id) && !is_null($model->publish_owner_id) && strlen($model->publish_owner_id)>0){	 
			  	 IjoyPlusServiceUtils::exportServiceError(Constants::PROGRAM_IS_PUBLISHED);
			}else {
		  	  $model->publish_owner_id=$owner_id;	
			  $model->save();
			  $dynamic = new Dynamic();
			  $dynamic->author_id=$owner_id;
			  $dynamic->content_id=$model->d_id;
		   	  $dynamic->status=Constants::OBJECT_APPROVAL;
			  $dynamic->create_date=new CDbExpression('NOW()');
			  $dynamic->content_type=$model->d_type;
			  $dynamic->content_name=$model->d_name;
			  $dynamic->dynamic_type=Constants::DYNAMIC_TYPE_PUBLISH_PROGRAM;
			  $dynamic->content_pic_url=$model->d_pic;
			  $dynamic->save();
			  $transaction->commit();
		      IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
			 }			  
		   }else {
		      IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
		  }
		}catch (Exception $e){
		    $transaction->rollback();
			IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
	    }		
	}

	function actionLike(){
        header('Content-type: application/json');
	    if(!Yii::app()->request->isPostRequest){   
	   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
	   		 return ;
	   	}
	    if(!IjoyPlusServiceUtils::validateAPPKey()){
  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
		   return ;
		}
		if(IjoyPlusServiceUtils::validateUserID()){
			IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
			return ;
		}
		$prod_id= Yii::app()->request->getParam("prod_id");
		if( (!isset($prod_id)) || is_null($prod_id)  ){
			IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
			return;
		}
		$program = Program::model()->findByPk($prod_id);
		if($program !== null){
			$owner_id=Yii::app()->user->id;
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$program->love_user_count=$program->love_user_count+1;
//				$program->save();
				Program::model()->incLoveUserCount($prod_id);
				
				CacheManager::synProgramCache($program);

				$dynamic = new Dynamic();
				$dynamic->author_id=$owner_id;
				$dynamic->content_id=$program->d_id;
				$dynamic->status=Constants::OBJECT_APPROVAL;
				$dynamic->create_date=new CDbExpression('NOW()');
				$dynamic->content_type=$program->d_type;
				$dynamic->content_name=$program->d_name;
				$dynamic->dynamic_type=Constants::DYNAMIC_TYPE_LIKE;
				$dynamic->content_pic_url=$program->d_pic;
				$dynamic->save();

				if(isset($program->publish_owner_id) && !is_null($program->publish_owner_id)){

					if($program->publish_owner_id !== $owner_id){
						// add notify msg
						$msg = new NotifyMsg();
						$msg->author_id=$program->publish_owner_id;
						$msg->nofity_user_id=Yii::app()->user->id;
						$msg->notify_user_name=Yii::app()->user->getState("nickname");
						$msg->notify_user_pic_url=Yii::app()->user->getState("pic_url");
						$msg->content_id=$program->d_id;
						$msg->content_info=$program->d_name;
						$msg->content_type=$program->d_type;
						$msg->created_date=new CDbExpression('NOW()');
						$msg->status=Constants::OBJECT_APPROVAL;
						$msg->notify_type=Constants::NOTIFY_TYPE_LIKE_PROGRAM;
						$msg->save();
					}
				}
				$transaction->commit();
				IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
			} catch (Exception $e) {
				$transaction->rollback();
				IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
			}
			}else {
				IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
			}
		}
		
        function actionSupport(){
	        header('Content-type: application/json');
		    if(!Yii::app()->request->isPostRequest){   
		   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
		   		 return ;
		   	}
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			$program = Program::model()->findByPk($prod_id);
			if($program !== null){
			     $program->good_number=$program->good_number+1;
				 Program::model()->incGoodCount($prod_id);
				 CacheManager::synProgramCache($program);
				 if(IjoyPlusServiceUtils::validateUserID()){
					IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);	
					return ;
				 }
				$owner_id=Yii::app()->user->id;
				$transaction = Yii::app()->db->beginTransaction();
				try {
                    $favority = Dynamic::model()->getDynamicByProd($owner_id, $prod_id,Constants::DYNAMIC_TYPE_MAKE_GOOD);
                 	if(!(isset($favority) && !is_null($favority))){
						$dynamic = new Dynamic();
						$dynamic->author_id=$owner_id;
						$dynamic->content_id=$program->d_id;
						$dynamic->status=Constants::OBJECT_APPROVAL;
						$dynamic->create_date=new CDbExpression('NOW()');
						$dynamic->content_type=$program->d_type;
						$dynamic->content_name=$program->d_name;
						$dynamic->dynamic_type=Constants::DYNAMIC_TYPE_MAKE_GOOD;
						$dynamic->content_pic_url=$program->d_pic;
						$dynamic->save();
	                    User::model()->updateProgramGoodCount($owner_id, 1);
                 	}else {
                 	   IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_EXIST);
                 	}
					$transaction->commit();
					IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
				} catch (Exception $e) {
					$transaction->rollback();
					IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
				}
			}else {
				IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
			}
		}
        function actionWatch(){
	        header('Content-type: application/json');
		    if(!Yii::app()->request->isPostRequest){   
		   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
		   		 return ;
		   	}
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
	        if(IjoyPlusServiceUtils::validateUserID()){
				IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
				return ;
			}
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			$program = Program::model()->findByPk($prod_id);
			if($program !== null){
				$owner_id=Yii::app()->user->id;
				$transaction = Yii::app()->db->beginTransaction();
				try {
                    $favority = Dynamic::model()->getDynamicByProd($owner_id, $prod_id,Constants::DYNAMIC_TYPE_WATCH);
                 	if(!(isset($favority) && !is_null($favority))){
						$dynamic = new Dynamic();
						$dynamic->author_id=$owner_id;
						$dynamic->content_id=$program->d_id;
						$dynamic->status=Constants::OBJECT_APPROVAL;
						$dynamic->create_date=new CDbExpression('NOW()');
						$dynamic->content_type=$program->d_type;
						$dynamic->content_name=$program->d_name;
						$dynamic->dynamic_type=Constants::DYNAMIC_TYPE_WATCH;
						$dynamic->content_pic_url=$program->d_pic;
						$dynamic->save();
//                 	  }
	                    $program->watch_user_count=$program->watch_user_count+1;
						Program::model()->incWatchUserCount($prod_id);
						CacheManager::synProgramCache($program);
//						if(isset($program->publish_owner_id) && !is_null($program->publish_owner_id) && $program->publish_owner_id !== $owner_id){
//							// add notify msg
//							$msg = new NotifyMsg();
//							$msg->author_id=$program->publish_owner_id;
//							$msg->nofity_user_id=Yii::app()->user->id;
//							$msg->notify_user_name=Yii::app()->user->getState("username");
//							$msg->notify_user_pic_url=Yii::app()->user->getState("pic_url");
//							$msg->content_id=$program->d_id;
//							$msg->content_info=$program->d_name;
//							$msg->content_type=$program->d_type;
//							$msg->created_date=new CDbExpression('NOW()');
//							$msg->status=Constants::OBJECT_APPROVAL;
//							$msg->notify_type=Constants::NOTIFY_TYPE_FAVORITY;
//							$msg->save();
//						}
                 	}else {
                 	   IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_EXIST);
                 	}
					$transaction->commit();
					IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
				} catch (Exception $e) {
					$transaction->rollback();
					IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
				}
			}else {
				IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
			}
		}

		function actionFavority(){
	        header('Content-type: application/json');
		    if(!Yii::app()->request->isPostRequest){   
		   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
		   		 return ;
		   	}
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
	        if(IjoyPlusServiceUtils::validateUserID()){
				IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
				return ;
			}
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			$program = Program::model()->findByPk($prod_id);
			if($program !== null){
				$owner_id=Yii::app()->user->id;
				$transaction = Yii::app()->db->beginTransaction();
				try {
                    $favority = Dynamic::model()->getDynamicByProd($owner_id, $prod_id,Constants::DYNAMIC_TYPE_FAVORITY);
                 	if(!(isset($favority) && !is_null($favority) && $favority->status ==Constants::OBJECT_APPROVAL)){
                 	  if(isset($favority) && !is_null($favority)) {
                 	     $favority->status=Constants::OBJECT_APPROVAL;
                 	     $favority->create_date=new CDbExpression('NOW()');
                 	     $favority->save();
                 	  }else{
						$dynamic = new Dynamic();
						$dynamic->author_id=$owner_id;
						$dynamic->content_id=$program->d_id;
						$dynamic->status=Constants::OBJECT_APPROVAL;
						$dynamic->create_date=new CDbExpression('NOW()');
						$dynamic->content_type=$program->d_type;
						$dynamic->content_name=$program->d_name;
						$dynamic->dynamic_type=Constants::DYNAMIC_TYPE_FAVORITY;
						$dynamic->content_pic_url=$program->d_pic;
						$dynamic->save();
                 	  }
	                    $program->favority_user_count=$program->favority_user_count+1;
//						$program->save();
                        Program::model()->incFavorityUserCount($prod_id);
                 	    User::model()->updateFavorityCount($owner_id, 1);
						CacheManager::synProgramCache($program);
//						if(isset($program->publish_owner_id) && !is_null($program->publish_owner_id) && $program->publish_owner_id !== $owner_id){
//							// add notify msg
//							$msg = new NotifyMsg();
//							$msg->author_id=$program->publish_owner_id;
//							$msg->nofity_user_id=Yii::app()->user->id;
//							$msg->notify_user_name=Yii::app()->user->getState("username");
//							$msg->notify_user_pic_url=Yii::app()->user->getState("pic_url");
//							$msg->content_id=$program->d_id;
//							$msg->content_info=$program->d_name;
//							$msg->content_type=$program->d_type;
//							$msg->created_date=new CDbExpression('NOW()');
//							$msg->status=Constants::OBJECT_APPROVAL;
//							$msg->notify_type=Constants::NOTIFY_TYPE_FAVORITY;
//							$msg->save();
//						}
                 	}else {
                 	   IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_EXIST);
                 	}
					$transaction->commit();
					IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
				} catch (Exception $e) {
					$transaction->rollback();
					IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
				}
			}else {
				IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
			}
		}
		
        function actionRecommend(){
	        header('Content-type: application/json');
		    if(!Yii::app()->request->isPostRequest){   
		   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
		   		 return ;
		   	}
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
	        if(IjoyPlusServiceUtils::validateUserID()){
				IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
				return ;
			}
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			
			$program = Program::model()->findByPk($prod_id);
			if($program !== null){
				$owner_id=Yii::app()->user->id;
				$transaction = Yii::app()->db->beginTransaction();
				try {
                    $favority = Dynamic::model()->getDynamicByProd($owner_id, $prod_id,Constants::DYNAMIC_TYPE_RECOMMEND);
//                 	if(!(isset($favority) && !is_null($favority) && $favority->status ==Constants::OBJECT_APPROVAL)){
                    if(!(isset($favority) && !is_null($favority))){
//                 		$program->love_user_count=$program->love_user_count+1;
//				        $program->save();
//				        CacheManager::synProgramCache($program);
//                 	  if(isset($favority) && !is_null($favority)) {
//                 	     $favority->status=Constants::OBJECT_APPROVAL;
//                 	     $favority->create_date=new CDbExpression('NOW()');                 	     
//                 	     $favority->save();
//                 	  }else{
						$dynamic = new Dynamic();
						$dynamic->author_id=$owner_id;
						$dynamic->content_id=$program->d_id;
						$dynamic->status=Constants::OBJECT_APPROVAL;
						$dynamic->create_date=new CDbExpression('NOW()');
						$dynamic->content_type=$program->d_type;
						$dynamic->content_name=$program->d_name;						
						$dynamic->content_desc=Yii::app()->request->getParam("reason");
						$dynamic->dynamic_type=Constants::DYNAMIC_TYPE_RECOMMEND;
						$dynamic->content_pic_url=$program->d_pic;
						$dynamic->save();
//                 	  }
	                    
//						if(isset($program->publish_owner_id) && !is_null($program->publish_owner_id) && $program->publish_owner_id !== $owner_id){
//							// add notify msg
//							$msg = new NotifyMsg();
//							$msg->author_id=$program->publish_owner_id;
//							$msg->nofity_user_id=Yii::app()->user->id;
//							$msg->notify_user_name=Yii::app()->user->getState("username");
//							$msg->notify_user_pic_url=Yii::app()->user->getState("pic_url");
//							$msg->content_id=$program->d_id;
//							$msg->content_info=$program->d_name;
//							$msg->content_type=$program->d_type;
//							$msg->created_date=new CDbExpression('NOW()');
//							$msg->status=Constants::OBJECT_APPROVAL;
//							$msg->notify_type=Constants::NOTIFY_TYPE_FAVORITY;
//							$msg->save();
//						}
                 	}else {
                 	   IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
                 	}
					$transaction->commit();
					IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
				} catch (Exception $e) {
					$transaction->rollback();
					IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
				}
			}else {
				IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
			}
		}
		
       function actionHiddenWatch(){
	        header('Content-type: application/json');
		    if(!Yii::app()->request->isPostRequest){   
		   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
		   		 return ;
		   	}
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
	        if(IjoyPlusServiceUtils::validateUserID()){
				IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
				return ;
			}
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			$program = CacheManager::getProgramCache($prod_id);
			if($program !== null){
				$owner_id=Yii::app()->user->id;
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$favority = Dynamic::model()->getDynamicByProd($owner_id ,$prod_id,Constants::DYNAMIC_TYPE_WATCH);
                 	if(isset($favority) && !is_null($favority) && $favority->status ==Constants::OBJECT_APPROVAL){                 		                 		
						$favority->status=Constants::OBJECT_DELETE;						
						$favority->save();
//						$dynamic = new Dynamic();
//						$dynamic->author_id=$owner_id;
//						$dynamic->content_id=$program->d_id;
//						$dynamic->status=Constants::OBJECT_APPROVAL;
//						$dynamic->create_date=new CDbExpression('NOW()');
//						$dynamic->content_type=$program->d_type;
//						$dynamic->content_name=$program->d_name;
//						$dynamic->dynamic_type=Constants::DYNAMIC_TYPE_UN_FAVORITY;
//						$dynamic->content_pic_url=$program->d_pic;
//						$dynamic->save();
//	
//						if(isset($program->publish_owner_id) && !is_null($program->publish_owner_id) && $program->publish_owner_id !== $owner_id){
//							// add notify msg
//							$msg = new NotifyMsg();
//							$msg->author_id=$program->publish_owner_id;
//							$msg->nofity_user_id=Yii::app()->user->id;
//							$msg->notify_user_name=Yii::app()->user->getState("username");
//							$msg->notify_user_pic_url=Yii::app()->user->getState("pic_url");
//							$msg->content_id=$program->d_id;
//							$msg->content_info=$program->d_name;
//							$msg->content_type=$program->d_type;
//							$msg->created_date=new CDbExpression('NOW()');
//							$msg->status=Constants::OBJECT_APPROVAL;
//							$msg->notify_type=Constants::NOTIFY_TYPE_UN_FAVORITY;
//							$msg->save();
//						}
                 	}
					$transaction->commit();
					IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
				} catch (Exception $e) {
					$transaction->rollback();
					IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
				}
			}else {
				IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
			}
		}
		
		function actionUnfavority(){
	        header('Content-type: application/json');
		    if(!Yii::app()->request->isPostRequest){   
		   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
		   		 return ;
		   	}
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
	        if(IjoyPlusServiceUtils::validateUserID()){
				IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
				return ;
			}
			
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			
			$program = Program::model()->findByPk($prod_id);
			if($program !== null){
				$owner_id=Yii::app()->user->id;
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$favority = Dynamic::model()->getFavorityByProd($owner_id, $prod_id);
                 	if(isset($favority) && !is_null($favority) && $favority->status ==Constants::OBJECT_APPROVAL){
                 		
						$favority->status=Constants::OBJECT_DELETE;
						$favority->save();
                 	    if( $program->favority_user_count >=1){
		                   $program->favority_user_count=$program->favority_user_count-1;
		                   $program->save();
		                 }
		                 if( $program->favority_user_count <0){
		                 	$program->favority_user_count=0;
		                    $program->save();
		                 }
						User::model()->updateFavorityCount($owner_id, -1);
	                    CacheManager::synProgramCache($program);
//						$dynamic = new Dynamic();
//						$dynamic->author_id=$owner_id;
//						$dynamic->content_id=$program->d_id;
//						$dynamic->status=Constants::OBJECT_APPROVAL;
//						$dynamic->create_date=new CDbExpression('NOW()');
//						$dynamic->content_type=$program->d_type;
//						$dynamic->content_name=$program->d_name;
//						$dynamic->dynamic_type=Constants::DYNAMIC_TYPE_UN_FAVORITY;
//						$dynamic->content_pic_url=$program->d_pic;
//						$dynamic->save();
	
//						if(isset($program->publish_owner_id) && !is_null($program->publish_owner_id) && $program->publish_owner_id !== $owner_id){
//							// add notify msg
//							$msg = new NotifyMsg();
//							$msg->author_id=$program->publish_owner_id;
//							$msg->nofity_user_id=Yii::app()->user->id;
//							$msg->notify_user_name=Yii::app()->user->getState("username");
//							$msg->notify_user_pic_url=Yii::app()->user->getState("pic_url");
//							$msg->content_id=$program->d_id;
//							$msg->content_info=$program->d_name;
//							$msg->content_type=$program->d_type;
//							$msg->created_date=new CDbExpression('NOW()');
//							$msg->status=Constants::OBJECT_APPROVAL;
//							$msg->notify_type=Constants::NOTIFY_TYPE_UN_FAVORITY;
//							$msg->save();
//						}
                 	}
					$transaction->commit();
					IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
				} catch (Exception $e) {
					$transaction->rollback();
					IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
				}
			}else {
				IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
			}
		}
		

		function actionHiddenRecommend(){
	        header('Content-type: application/json');
		    if(!Yii::app()->request->isPostRequest){   
		   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
		   		 return ;
		   	}
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
	        if(IjoyPlusServiceUtils::validateUserID()){
				IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
				return ;
			}
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			$program = Program::model()->findByPk($prod_id);
			if($program !== null){
				$owner_id=Yii::app()->user->id;
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$favority = Dynamic::model()->getDynamicByProd($owner_id, $prod_id,Constants::DYNAMIC_TYPE_RECOMMEND);
                 	if(isset($favority) && !is_null($favority) && $favority->status ==Constants::OBJECT_APPROVAL){                 		
						$favority->status=Constants::OBJECT_DELETE;
						$favority->save();
						
//						$program->love_user_count=$program->love_user_count-1;
//						$program->save();
//	                    CacheManager::synProgramCache($program);
//						$dynamic = new Dynamic();
//						$dynamic->author_id=$owner_id;
//						$dynamic->content_id=$program->d_id;
//						$dynamic->status=Constants::OBJECT_APPROVAL;
//						$dynamic->create_date=new CDbExpression('NOW()');
//						$dynamic->content_type=$program->d_type;
//						$dynamic->content_name=$program->d_name;
//						$dynamic->dynamic_type=Constants::DYNAMIC_TYPE_UN_FAVORITY;
//						$dynamic->content_pic_url=$program->d_pic;
//						$dynamic->save();
//	
//						if(isset($program->publish_owner_id) && !is_null($program->publish_owner_id) && $program->publish_owner_id !== $owner_id){
//							// add notify msg
//							$msg = new NotifyMsg();
//							$msg->author_id=$program->publish_owner_id;
//							$msg->nofity_user_id=Yii::app()->user->id;
//							$msg->notify_user_name=Yii::app()->user->getState("username");
//							$msg->notify_user_pic_url=Yii::app()->user->getState("pic_url");
//							$msg->content_id=$program->d_id;
//							$msg->content_info=$program->d_name;
//							$msg->content_type=$program->d_type;
//							$msg->created_date=new CDbExpression('NOW()');
//							$msg->status=Constants::OBJECT_APPROVAL;
//							$msg->notify_type=Constants::NOTIFY_TYPE_UN_FAVORITY;
//							$msg->save();
//						}
                 	}
					$transaction->commit();
					IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
				} catch (Exception $e) {
					$transaction->rollback();
					IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
				}
			}else {
				IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
			}
		}

		function actionShare(){
	        header('Content-type: application/json');
		    if(!Yii::app()->request->isPostRequest){   
		   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
		   		 return ;
		   	}
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
	        if(IjoyPlusServiceUtils::validateUserID()){
				IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
				return ;
			}
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			$program = Program::model()->findByPk($prod_id);
			if($program !== null){
				$owner_id=Yii::app()->user->id;
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$favority = Dynamic::model()->getDynamicByProd($owner_id, $prod_id,Constants::DYNAMIC_TYPE_SHARE);
                 	if(!(isset($favority) && !is_null($favority) )){                 		
						$dynamic = new Dynamic();
						$dynamic->author_id=$owner_id;
						$dynamic->content_id=$program->d_id;
						$dynamic->status=Constants::OBJECT_APPROVAL;
						$dynamic->create_date=new CDbExpression('NOW()');
						$dynamic->content_type=$program->d_type;
						$dynamic->content_name=$program->d_name;
						$dynamic->dynamic_type=Constants::DYNAMIC_TYPE_SHARE;
						$dynamic->content_pic_url=$program->d_pic;
//						$dynamic->content_desc=$share_to_where;
						$dynamic->save();
                 		User::model()->updateShareCount($owner_id, 1);
                 		 Program::model()->incShareCount($prod_id);
                 		$program->share_number=$program->share_number+1;
						CacheManager::synProgramCache($program);
                 	}
//					if(isset($program->publish_owner_id) && !is_null($program->publish_owner_id) && $program->publish_owner_id !== $owner_id){
//						// add notify msg
//						$msg = new NotifyMsg();
//						$msg->author_id=$program->publish_owner_id;
//						$msg->nofity_user_id=Yii::app()->user->id;
//						$msg->notify_user_name=Yii::app()->user->getState("username");
//						$msg->notify_user_pic_url=Yii::app()->user->getState("pic_url");
//						$msg->content_id=$program->d_id;
//						$msg->content_info=$program->d_name;
//						$msg->content_type=$program->d_type;
//						$msg->created_date=new CDbExpression('NOW()');
//						$msg->status=Constants::OBJECT_APPROVAL;
//						$msg->notify_type=Constants::NOTIFY_TYPE_SHARE;
//						$msg->content_desc=$share_to_where;
//						$msg->save();
//					}
					$transaction->commit();
					IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
				} catch (Exception $e) {
					$transaction->rollback();
					IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
				}
			}else {
				IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
			}
		}

		public function  actionComments(){
	        header('Content-type: application/json');
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
			
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			$page_size=Yii::app()->request->getParam("page_size");
			$page_num=Yii::app()->request->getParam("page_num");
			if(!(isset($page_size) && is_numeric($page_size))){
				$page_size=10;
				$page_num=1;
			}else if(!(isset($page_num) && is_numeric($page_num))){
				$page_num=1;
			}
			$comments= Comment::model()->getCommentsByProgram($prod_id,$page_size,$page_size*($page_num-1));
			if(isset($comments) && is_array($comments)){
				$commentTemps = array();
				foreach ($comments as $comment){
					$commentTemps[]=IjoyPlusServiceUtils::transferComments($comment);
				}
				IjoyPlusServiceUtils::exportEntity(array('comments'=>$commentTemps));
			}else {
				IjoyPlusServiceUtils::exportEntity(array('comments'=>array()));
			}
		}
		/**
		 * Creates a new model.
		 * If creation is successful, the browser will be redirected to the 'view' page.
		 */
		public function actionComment(){
	        header('Content-type: application/json');
//		    if(!Yii::app()->request->isPostRequest){   
//		   		 IjoyPlusServiceUtils::exportServiceError(Constants::METHOD_NOT_SUPPORT);
//		   		 return ;
//		   	}
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
	        if(IjoyPlusServiceUtils::validateUserID()){
				IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
				return ;
			}
		    if(!IjoyPlusServiceUtils::checkCSRCToken()){
		     IjoyPlusServiceUtils::exportServiceError(Constants::DUPLICAT_REQUEST);	
		    	return ;			
		    }
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
			$model=new Comment;
			$model->status=Constants::OBJECT_APPROVAL;
			$model->create_date=new CDbExpression('NOW()');
			$model->comments = Yii::app()->request->getParam("content");
			$model->content_id = Yii::app()->request->getParam("prod_id");
			$model->author_id = Yii::app()->user->id;
			$model->author_username=Yii::app()->user->getState("nickname");
			$model->author_photo_url=Yii::app()->user->getState("pic_url");
//			var_dump($model->comments);
			if($model->createComments()){
		      IjoyPlusServiceUtils::exportServiceError(Constants::SUCC);
			}else{
		      IjoyPlusServiceUtils::exportServiceError(Constants::SYSTEM_ERROR);
			}
		}

		public function actionView(){
            header('Content-type: application/json');
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
//			if(Yii::app()->user->isGuest){
//				IjoyPlusServiceUtils::exportServiceError(Constants::SEESION_IS_EXPIRED);	
//				return ;
//			}
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}

			$program= CacheManager::getProgramCache($prod_id);

			if($program === null){
		      IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
		      return;
			}

			$prod =ProgramUtil::exportProgramEntity($program);

			$comments = Comment::model()->getCommentsByProgram($prod_id,10,0);
			$commentTemps = array();
			if(isset($comments) && is_array($comments)){
				foreach ($comments as $comment){
					$commentTemps[]=IjoyPlusServiceUtils::transferComments($comment);
				}
			}
			$prod['comments']=$commentTemps;
		    $topics= Topic::model()->getRelatedTops($prod_id);
		    if(isset($topics) && is_array($topics)){
		    	$prod['topics']=$topics;
		    }else{
		    	$prod['topics']=array();
		    }
			IjoyPlusServiceUtils::exportEntity($prod);
		}

		public function actionViewRecommend(){
	        header('Content-type: application/json');
		    if(!IjoyPlusServiceUtils::validateAPPKey()){
	  	  	   IjoyPlusServiceUtils::exportServiceError(Constants::APP_KEY_INVALID);		
			   return ;
			}
	        if(IjoyPlusServiceUtils::validateUserID()){
				IjoyPlusServiceUtils::exportServiceError(Constants::USER_ID_INVALID);	
				return ;
			}
			$prod_id= Yii::app()->request->getParam("prod_id");
			if( (!isset($prod_id)) || is_null($prod_id)  ){
				IjoyPlusServiceUtils::exportServiceError(Constants::PARAM_IS_INVALID);
				return;
			}
	
		    $program= Program::model()->findByPk($prod_id);
	
		    if($program === null){
		    	IjoyPlusServiceUtils::exportServiceError(Constants::OBJECT_NOT_FOUND);
		    	return;
		    }
		    
			$userid=Yii::app()->request->getParam("user_id");
	   		if( (!isset($userid)) || is_null($userid)  ){
				$userid=Yii::app()->user->id;	   			
	   		}
	
		    $prod =ProgramUtil::exportProgramEntity($program);
	        $reCom = Dynamic::model()->getDynamicByProd($userid,$prod_id ,Constants::DYNAMIC_TYPE_RECOMMEND);
	        if(isset($reCom) && !is_null($reCom)){
	          $prod['reason']=$reCom->content_desc;
	        }
		    $comments = Comment::model()->getCommentsByProgram($prod_id,10,0);
		    if(isset($comments) && is_array($comments)){
		    	$commentTemps = array();
		    	foreach ($comments as $comment){
		    		$commentTemps[]=IjoyPlusServiceUtils::transferComments($comment);
		    	}
		    	$prod['comments']=$commentTemps;
		    }else {
		    	$prod['comments']=array();
		    }
		    $dynamic = Dynamic::model()->friendDynamicForProgram(Yii::app()->user->id,$prod_id,10,0);
		    if(isset($dynamic) && is_array($dynamic)){
		    	$prod['dynamics']=$this->transferDynamics($dynamic);
		    }else{
		    	$prod['dynamics']=array();
		    }
		    $topics= Topic::model()->getRelatedTops($prod_id);
		    if(isset($topics) && is_array($topics)){
		    	$prod['topics']=$topics;
		    }else{
		    	$prod['topics']=array();
		    }
		    IjoyPlusServiceUtils::exportEntity($prod);
		}
		
       private function transferDynamics($dynamics){
    	 $temp =array();
    	 foreach ($dynamics as $dynamic){
    	   switch ($dynamic['dynamic_type']){
    	   	case Constants::DYNAMIC_TYPE_WATCH:
    	   	  $temp[] = array(
    	   	    'type'=>'watch',
    	   	    'user_id'=>$dynamic['friend_id'],
    	   	    'user_name'=>$dynamic['friend_username'],
    	   	    'user_pic_url'=>$dynamic['friend_photo_url'],
    	   	    'create_date'=>$dynamic['create_date'],
    	   	  );
    	   	  break;

    	   	  case Constants::DYNAMIC_TYPE_SHARE:
    	   	  $temp[] = array(
    	   	    'type'=>'share',
    	   	    'user_id'=>$dynamic['friend_id'],
    	   	    'user_name'=>$dynamic['friend_username'],
    	   	    'user_pic_url'=>$dynamic['friend_photo_url'],
    	   	    'create_date'=>$dynamic['create_date'],
    	   	    'share_where_type'=>$dynamic['content_desc'],
    	   	  );
    	   	  break;
    	   	  
    	   	  case Constants::DYNAMIC_TYPE_COMMENTS:
    	   	  $temp[] = array(
    	   	    'type'=>'comment',
    	   	    'user_id'=>$dynamic['friend_id'],
    	   	    'user_name'=>$dynamic['friend_username'],
    	   	    'user_pic_url'=>$dynamic['friend_photo_url'],
    	   	    'create_date'=>$dynamic['create_date'],
    	   	    'content'=>$dynamic['content_desc'],
    	   	  );
    	   	  break;
    	   	  
    	   	  case Constants::DYNAMIC_TYPE_FAVORITY:
    	   	  $temp[] = array(
    	   	    'type'=>'favority',
    	   	    'user_id'=>$dynamic['friend_id'],
    	   	    'user_name'=>$dynamic['friend_username'],
    	   	    'user_pic_url'=>$dynamic['friend_photo_url'],
    	   	    'create_date'=>$dynamic['create_date'],
    	   	  );
    	   	  break;
    	   	  
    	   	  case Constants::DYNAMIC_TYPE_LIKE:
    	   	  $temp[] = array(
    	   	    'type'=>'like',
    	   	    'user_id'=>$dynamic['friend_id'],
    	   	    'user_name'=>$dynamic['friend_username'],
    	   	    'user_pic_url'=>$dynamic['friend_photo_url'],
    	   	    'create_date'=>$dynamic['create_date'],
    	   	  );
    	   	  break;
    	   	  
    	   	 case Constants::DYNAMIC_TYPE_PUBLISH_PROGRAM:
    	   	  $temp[] = array(
    	   	    'type'=>'publish',
    	   	    'user_id'=>$dynamic['friend_id'],
    	   	    'user_name'=>$dynamic['friend_username'],
    	   	    'user_pic_url'=>$dynamic['friend_photo_url'],
    	   	    'create_date'=>$dynamic['create_date'],
    	   	  );
    	   	  break;
    	   	  
    	   	  case Constants::DYNAMIC_TYPE_RECOMMEND:
    	   	  $temp[] = array(
    	   	    'type'=>'recommend',
    	   	    'user_id'=>$dynamic['friend_id'],
    	   	    'user_name'=>$dynamic['friend_username'],
    	   	    'user_pic_url'=>$dynamic['friend_photo_url'],
    	   	    'create_date'=>$dynamic['create_date'],
    	   	    'reason'=>$dynamic['content_desc'],
    	   	  );
    	   	  break;
    	   	  
    	   }
    	}
    	return $temp;
    }
	
	}