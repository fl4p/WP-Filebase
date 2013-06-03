<?php /* NOT USED class WPFB_Config {
	static $file;
	
	static function InitClass()  {
		self::$file = ABSPATH.'wp-content/wp-filebase-config.php';
	}
	
	static function IsWritable() {
		return (@is_writable(self::$file) || self::Update(self::$file)); 	
	}
	
	static function Update() {
		$fh = @fopen(self::$file, 'w');
		if(!$fh) return false;
		
		@fclose($fh);
	}
}
*/