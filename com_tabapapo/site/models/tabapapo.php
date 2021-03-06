<?php
/**
 * @package Tabapapo Component for Joomla! 3.9
 * @version 0.8.5
 * @author Jonatas C. Ferreira
 * @copyright (C) 2021 Tabaoca.org
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Date\Date;

class TabaPapoModelTabaPapo extends FormModel
{

	/**
	 * @var object item
	 */
	protected $item;
   //protected $form;

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return	void
	 * @since	2.5
	 */
	protected function populateState()
	{
		// Get the message id
		$jinput = JFactory::getApplication()->input;
		$id     = $jinput->get('id', 1, 'INT');
		$this->setState('message.id', $id);

		// Load the parameters.
		$this->setState('params', JFactory::getApplication()->getParams());
		parent::populateState();
	}
	
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   1.6
	 */
	public function getTable($type = 'TabaPapo', $prefix = 'TabaPapoTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	public function getScript() 
	{
		return '/media/com_tabapapo/js/systabapapo.js';
	}
	
	/**
	 * Get the message
	 * @return object The message to be displayed to the user
	 */
	public function getItem()
	{
		if (!isset($this->item)) 
		{
			$id    = $this->getState('message.id');
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('h.id, h.title, h.params, h.imagem as imagem, c.title as category,
                         h.alias, h.catid, h.description')
				  ->from('#__tabapapo as h')
				  ->leftJoin('#__categories as c ON h.catid=c.id')
				  ->where('h.id=' . (int)$id);
			$db->setQuery((string)$query);
		
			if ($this->item = $db->loadObject()) 
			{
				// Load the JSON string
				$params = new JRegistry;
				$params->loadString($this->item->params, 'JSON');
				$this->item->params = $params;

				// Merge global params with item params
				$params = clone $this->getState('params');
				$params->merge($this->item->params);
				$this->item->params = $params;
				
				// Convert the JSON-encoded image info into an array
				$imagem = new JRegistry;
				$imagem->loadString($this->item->imagem, 'JSON');
				$this->item->imageDetails = $imagem;
			}
		}
		return $this->item;
	}
   
	public function getForm($data = array(), $loadData = false)
	{
		$form = $this->loadForm(
			'com_tabapapo.tabapapoform',  // just a unique name to identify the form
			'tabapapo-form',				// the filename of the XML form definition
										// Joomla will look in the models/forms folder for this file
			array(
				'control' => 'jform',	// the name of the array for the POST parameters
				'load_data' => $loadData	// will be TRUE
			)
		);

		return $form;
	}

    protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState(
			'com_tabapapo.tabapapoform',	// a unique name to identify the data in the session
			array("reservado" => "0")	// prefill data if no data found in session
		);

		return $data;
	}

	public function enviarMensagem($form)
	{

         $currentuser = JFactory::getuser();

         $db = JFactory::getDbo();

			$querynow = $db->getQuery(true);

			$querynow->select('now() as now');
   			
   		$db->setQuery($querynow);
               			
         $resultnow = $db->loadObjectList();
                     
		try 
		{         
         $msgchat = new stdClass();
			$msgchat->reservado = $form['privado'];
			$msgchat->sala_id = $form['sala_id'];
			$msgchat->usu_id = $currentuser->get("id");
			$msgchat->params = $currentuser->get("username");;
			$msgchat->msg = $form['msg2'];
			$msgchat->falacom_id = $form['falacom_id'];
			$msgchat->tempo = $resultnow[0]->now;

			$resultmsg = JFactory::getDbo()->insertObject('#__tabapapo_msg', $msgchat);
         
		}
		catch (Exception $e)
		{
			$msg = $e->getMessage();
			JFactory::getApplication()->enqueueMessage($msg, 'error'); 
			$resultmsg = null;
		}

		return $resultmsg; 
	}

	public function enviarMensagemSys($form, $type)
	{

      $currentuser = JFactory::getuser();

      $db = JFactory::getDbo();

		$querynow = $db->getQuery(true);

		$querynow->select('now() as now');
		
		$db->setQuery($querynow);
            			
      $resultnow = $db->loadObjectList();
      
      try 
		{         
         $msgchat = new stdClass();
			
         if ($type == 1) {
            $msgchat->msg = $form->params.' entered.';
         }
         
         if ($type == 2) {
            $msgchat->msg = $form->params.' left.';
         }
         
         $msgchat->reservado = $form->privado;
			$msgchat->sala_id = $form->sala_id;
			$msgchat->usu_id = 0; //System Id
			$msgchat->params = $form->params;
   		$msgchat->falacom_id = $form->falacom_id;
			$msgchat->tempo = $resultnow[0]->now;

			$resultmsg = JFactory::getDbo()->insertObject('#__tabapapo_msg', $msgchat);
         
		}
		catch (Exception $e)
		{
			$msg = $e->getMessage();
			JFactory::getApplication()->enqueueMessage($msg, 'error'); 
			$resultmsg = null;
		}

		return $resultmsg; 
	}

	public function  msgslerB($sala_id) {
	   
      $currentuser = JFactory::getuser();

      $input = Factory::getApplication()->input;

      $lmsg_id = $input->get('lmsg',0,'INT');
         
         if($currentuser->get("id") > 0){
   		try {  
            $usu_id = $currentuser->get("id");

   			$db = JFactory::getDbo();
         
            $id = $this->atualizarTempo($usu_id, $sala_id);
            
   			$query = $db->getQuery(true);

   			$query->select($db->quoteName(array('id','sala_id','usu_id','reservado','msg','falacom_id','params','tempo')));
   			$query->from($db->quoteName('#__tabapapo_msg'));
   			$query->where($db->quoteName('sala_id').'='.$db->quote($sala_id));
            $query->where($db->quoteName('id').'>'.$db->quote($lmsg_id));
            $query->order($db->quoteName('id'), 'ASC');
            
   			$db->setQuery($query);
               			
            $results = $db->loadObjectList();
            
            return $results;
           }
           
   		catch (Exception $e)
   		{
   			$msg = $e->getMessage();
   			JFactory::getApplication()->enqueueMessage($msg, 'error'); 
   			$resultmsg = null;
   		  }
           
      	}
   		else {
   			header('Location:index.php');
   		}
      

	}

	public function  userslerB($sala_id) {
	   
      $currentuser = JFactory::getuser();

      $input = Factory::getApplication()->input;
         
         if($currentuser->get("id") > 0){
         
   		try {
         
            $db = JFactory::getDbo();

      		$querynow = $db->getQuery(true);

      		$querynow->select('now() as now');
      		
      		$db->setQuery($querynow);
                  			
            $resultnow = $db->loadObjectList();
         
            $usu_id = $currentuser->get("id");

            $object = new stdClass();
            $object->id = $usu_id;
            $object->tempo = $resultnow[0]->now;

   			$db = JFactory::getDbo();
   			            
            $delusers = $this->deleteUsers($sala_id);
            $delmsgs = $this->deleteMsgs($sala_id);
            
            $query = $db->getQuery(true);

   			$query->select($db->quoteName(array('id','sala_id','usu_id','status','ip','params','tempo')));
   			$query->from($db->quoteName('#__tabapapo_usu'));
   			$query->where($db->quoteName('sala_id').'='.$db->quote($sala_id));
            $query->order($db->quoteName('params'), 'ASC');
            
   			$db->setQuery($query);
               			
            $results = $db->loadObjectList();
            
            return $results;
           }
           
   		catch (Exception $e)
   		{
   			$msg = $e->getMessage();
   			JFactory::getApplication()->enqueueMessage($msg, 'error'); 
   			$resultmsg = null;
   		  }
           
      	}
   		else {
   			header('Location:index.php');
   		}
      
	}

	public function  entrarSalaB($sala_id) {
	   
      $currentuser = JFactory::getuser();

      if ($currentuser->get("id") > 0) {
      
		try {  
      
         $usu_id = $currentuser->get("id");
         $usu_name = $currentuser->get("username");

         $db = JFactory::getDbo();

   		$querynow = $db->getQuery(true);

   		$querynow->select('now() as now');
   		
   		$db->setQuery($querynow);
               			
         $result = $db->loadObjectList();

         $db = JFactory::getDbo();

			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('id','usu_id')));
			$query->from($db->quoteName('#__tabapapo_usu'));
			$query->where($db->quoteName('usu_id').'='.$db->quote($usu_id));
         $query->where($db->quoteName('sala_id').'='.$db->quote($sala_id));

			$db->setQuery($query);
         $db->execute();
         $num_rows = $db->getNumRows();
			$results = $db->loadObjectlist();
         
         if ($num_rows == 0) {
            
         	$usuchat = new stdClass();
   			$usuchat->sala_id = $sala_id;
   			$usuchat->usu_id = $usu_id;
   			$usuchat->status ='1';
   			$usuchat->params = $usu_name;
   			$usuchat->ip = $_SERVER["REMOTE_ADDR"];
   			$usuchat->tempo = $result[0]->now;

   			$resultusu = JFactory::getDbo()->insertObject('#__tabapapo_usu', $usuchat);

            $this->enviarMensagemSys($usuchat,1);
         
			}

         return $results->id;
      }
        
		catch (Exception $e)
		{
			$msg = $e->getMessage();
			JFactory::getApplication()->enqueueMessage($msg, 'error'); 
			$resultmsg = null;
		  }
        
   	}
		else {
			header('Location:index.php');
		}
   

	}



   public function selectId($usu_id, $sala_id) {
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id','usu_id')));
		$query->from($db->quoteName('#__tabapapo_usu'));
		$query->where($db->quoteName('usu_id').'='.$db->quote($usu_id));
		$query->where($db->quoteName('sala_id').'='.$db->quote($sala_id));

		$db->setQuery($query);

		$results = $db->loadObjectList();

      return $results;

   }
   
   public function atualizarTempo($usu_id, $sala_id) {

      $db = JFactory::getDbo();
      
      $id = $this->selectId($usu_id, $sala_id);

      $db = JFactory::getDbo();

		$querynow = $db->getQuery(true);

		$querynow->select('now() as now');
		
		$db->setQuery($querynow);
            			
      $resultnow = $db->loadObjectList();

      $object = new stdClass();

      $object->id = $id[0]->id;
      $object->tempo = $resultnow[0]->now;

      $result = $db->updateObject('#__tabapapo_usu', $object, 'id');

      return true;

   }

   public function userstatusB($sala_id) {

      $currentuser = JFactory::getuser();
      $usu_id = $currentuser->get("id");
      $db = JFactory::getDbo();
      $id = $this->selectId($usu_id, $sala_id);
            
      $input = Factory::getApplication()->input;

      $status = $input->get('st',0,'INT');
            
      $object = new stdClass();
      $object->id = $id[0]->id;
      $object->status = $status;

      $result = $db->updateObject('#__tabapapo_usu', $object, 'id');
      
      return true;

   }
   
   public function atualizarParams($id, $params) {

      $object = new stdClass();

      $object->id = $id;
      $object->params = $params;

      $result = JFactory::getDbo()->updateObject('#__tabapapo_usu', $object, 'id');

   }
   
	public function  sairSalaB($sala_id) {
	   
      $currentuser = JFactory::getuser();
      $usu_id = $currentuser->get("id");
      $usu_name = $currentuser->get("username");;
         
      if ($usu_id > 0) {
   		try {      
      
            $db = JFactory::getDbo();
            
            $id = $this->selectId($usu_id, $sala_id);

      		$query = $db->getQuery(true);

      		$conditions = array($db->quoteName('id').' = '. $id[0]->id);

      		$query->delete($db->quoteName('#__tabapapo_usu'));
      		$query->where($conditions);

      		$db->setQuery($query);
      		$result = $db->execute();

            $userout = new stdClass();
            $userout->sala_id = $sala_id;
            $userout->privado = 0;
            $userout->falacom_id = 0;
            $userout->params = $usu_name;
            
            $msg = $this->enviarMensagemSys($userout, 2); //msg type 2 exit

         return true;	

           }
           
   		catch (Exception $e)
   		{
   			$msg = $e->getMessage();
   			JFactory::getApplication()->enqueueMessage($msg, 'error'); 
   			$resultmsg = null;
   		  }
           
      	}
   		else {
   			header('Location:index.php');
   		}

	}


	public function  deleteUsers($sala_id) {

		try {      
   
         $db = JFactory::getDbo();
         
         $querylist = $db->getQuery(true);
         
         $querylist->select($db->quoteName(array('id','sala_id','usu_id','status','ip','params','tempo')));
			$querylist->from($db->quoteName('#__tabapapo_usu'));
			$querylist->where($db->quoteName('sala_id').'='.$db->quote($sala_id));
   		$querylist->where('timestampdiff(second, tempo, now()) > 30');
         
         $db->setQuery($querylist);
         $db->execute();
         
         $num_rows = $db->getNumRows();
         $results = $db->loadObjectList();
         
         $db = JFactory::getDbo();
         
   		$query = $db->getQuery(true);

   		$query->delete($db->quoteName('#__tabapapo_usu'));
   		$query->where('timestampdiff(second, tempo, now()) > 30');

   		$db->setQuery($query);
   		$result = $db->execute();

         if ($num_rows > 0) {
            
            for ($i = 0; $i < $num_rows; $i++) {
            
               $userout = new stdClass();
               $userout->sala_id = $sala_id;
               $userout->privado = 0;
               $userout->falacom_id = 0;
               $userout->params = $results[$i]->params;

               $msgout = $this->enviarMensagemSys($userout,2);
               
            }
         }
         return true;	

        }
        
		catch (Exception $e)
		{
			$msg = $e->getMessage();
			JFactory::getApplication()->enqueueMessage($msg, 'error'); 
			$resultmsg = null;
		  }

	}
   
	public function  deleteMsgs($sala_id) {
	   
		try {      
   
         $db = JFactory::getDbo();
         
   		$query = $db->getQuery(true);

   		$query->delete($db->quoteName('#__tabapapo_msg'));
   		$query->where('timestampdiff(second, tempo, now()) > 300');

   		$db->setQuery($query);
   		$result = $db->execute();

         return true;	

        }
        
		catch (Exception $e)
		{
			$msg = $e->getMessage();
			JFactory::getApplication()->enqueueMessage($msg, 'error'); 
			$resultmsg = null;
		  }

	}

}
