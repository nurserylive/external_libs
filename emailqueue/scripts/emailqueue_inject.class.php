<?
	
	class emailqueue_inject
	{
        var $db_host;
        var $db_user;
        var $db_password;
        var $db_name;
        var $db;
        var $avoidpersistence;
        var $default_priority = 10;
        
        function emailqueue_inject($db_host, $db_user, $db_password, $db_name, $avoidpersistence = false, $emailqueue_timezone = false)
        {            
            $this->db_host = $db_host;
            $this->db_user = $db_user;
            $this->db_password = $db_password;
            $this->db_name = $db_name;
            $this->avoidpersistence = $avoidpersistence;
            if(!$emailqueue_timezone)
                $this->emailqueue_timezone = DEFAULT_TIMEZONE;
            else
                $this->emailqueue_timezone = $emailqueue_timezone;
        }
        
        function db_connect()
        {
            if(!$this->connectionid = mysqli_connect($this->db_host, $this->db_user, $this->db_password))
            {
                echo "Emailqueue Inject class component: Cannot connect to database";
                die;
            }
			mysqli_select_db($this->connectionid, $this->db_name);
			mysqli_query($this->connectionid, "set names utf8");
        }
        
        function db_disconnect()
        {
            mysqli_close($this->connectionid);
        }
        
        function inject
        (
            $foreign_id_a = null,
            $foreign_id_b = null,
            $priority = 10,
            $is_inmediate = true,
            $date_queued = null,
            $is_html = false,
            $from,
            $from_name = "",
            $to,
            $replyto = "",
            $replyto_name = "",
            $subject,
            $content,
            $content_nonhtml = "",
            $list_unsubscribe_url = ""
        )
        {
            $this->db_connect();
        
            $subject = str_replace("\\'", "'", $subject);
            $subject = str_replace("'", "\'", $subject);

            if(strlen($content) > 63000)
                $content = substr($content, 0, 63000);
            
            $content = str_replace("\\'", "'", $content);
            $content = str_replace("'", "\'", $content);
            
            $content_nonhtml = str_replace("\\'", "'", $content_nonhtml);
            $content_nonhtml = str_replace("'", "\'", $content_nonhtml);
            
            $result = mysqli_query
            (
				$this->connectionid,
				"
					insert into emails
					(
						foreign_id_a,
						foreign_id_b,
						priority,
						is_inmediate,
						is_sent,
						is_cancelled,
						is_blocked,
						is_sendingnow,
						send_count,
						error_count,
						date_injected,
						date_queued,
						date_sent,
						is_html,
						`from`,
						from_name,
						`to`,
						replyto,
						replyto_name,
						subject,
						content,
						content_nonhtml,
						list_unsubscribe_url
					)
					values
					(
						".($foreign_id_a ? $foreign_id_a : "null").",
						".($foreign_id_b ? $foreign_id_b : "null").",
						".($priority ? $priority : $this->default_priority).",
						".($is_inmediate ? "1" : "0").",
						0,
						0,
						0,
						0,
						0,
						0,
						'".date("Y-n-j H:i:s", $this->timestamp_adjust(mktime(), $this->emailqueue_timezone))."',
						".($date_queued ? "'".date("Y-n-j H:i:s", $this->timestamp_adjust($date_queued, $this->emailqueue_timezone))."'" : "null").",
						null,
						".($is_html ? "1" : "0").",
						".($from ? "'".$from."'" : "null").",
						".($from_name ? "'".$from_name."'" : "null").",
						".($to ? "'".$to."'" : "null").",
						".($replyto ? "'".$replyto."'" : "null").",
						".($replyto_name ? "'".$replyto_name."'" : "null").",
						'".$subject."',
						'".$content."',
						'".$content_nonhtml."',
						'".$list_unsubscribe_url."'
					)
				"
			);
            
            $this->db_disconnect();
            
            if($result)
                return true;
            else {
                mail("lorenzo@litmind.com", "Error Emailqueue", "Error while injecting");
                return false;
            }
        }

        function timestamp_adjust($timestamp, $to_timezone)
        {
            $datetime_object = new DateTime("@".$timestamp);

            $from_timezone_object = new DateTimeZone(date_default_timezone_get());
            $to_timezone_object = new DateTimeZone($to_timezone);

            $offset = $to_timezone_object->getOffset($datetime_object) - $from_timezone_object->getOffset($datetime_object);

            return $timestamp+$offset;
        }
        
        function destroy()
        {
        }
	}
	
?>
