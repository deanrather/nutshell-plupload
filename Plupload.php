<?php
namespace application\plugin\plupload
{
	use nutshell\Nutshell;
	use nutshell\behaviour\Singleton;
	use nutshell\core\plugin\Plugin;
	use application\plugin\plupload\PluploadException;
	
	class Plupload extends Plugin implements Singleton
	{
		public function init()
		{
			require_once(__DIR__._DS_.'PluploadException.php');
			require_once(__DIR__._DS_.'thirdparty'._DS_.'PluploadProcessor.php');
		}
		
		private $callback = null;
		
		public function getCallback()
		{
		    return $this->callback;
		}
		
		public function setCallback($callback)
		{
		    $this->callback = $callback;
		    return $this;
		}
		
		public function upload()
		{
			// Check for Data
			if(!isset($_SERVER["HTTP_CONTENT_TYPE"]) && !isset($_SERVER["CONTENT_TYPE"]))
			{
				throw new PluploadException(PluploadException::MUST_HAVE_DATA, $_SERVER);
			}
			
			$config = Nutshell::getInstance()->config;
			$temporary_dir = $config->plugin->Plupload->temporary_dir;
			
			$plupload = new \PluploadProcessor();
			$plupload->setTargetDir($temporary_dir);
			$plupload->setCallback(array($this, 'uploadComplete'));
			$plupload->process();
		}
		
		public function uploadComplete($filename)
		{
			$config = Nutshell::getInstance()->config;
			$completed_dir = $config->plugin->Plupload->completed_dir;
			$thumbnail_dir = $config->plugin->Plupload->thumbnail_dir;
			$pathinfo	= pathinfo($filename);
			$ext		= $pathinfo['extension'];
			$basename	= $pathinfo['basename'];
			
			// Create thumbnail
			if (!file_exists($thumbnail_dir)) @mkdir($thumbnail_dir);
			copy($filename, $thumbnail_dir.$basename); // Todo, this is a bit of a cop-out
			
			// Move to completed folder
			if (!file_exists($completed_dir)) @mkdir($completed_dir);
			rename($filename, $completed_dir.$basename);
			
			// process any extra stuff
			if($this->callback)
			{
				call_user_func_array
				(
					$this->callback,
					array($basename)
				 );
			}
		}
	}
}
