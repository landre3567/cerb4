<?php
class ChFeedbackPlugin extends DevblocksPlugin {
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class ChFeedbackTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return dirname(dirname(__FILE__)) . '/strings.xml';
		}
	};
endif;
