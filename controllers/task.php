<?php
namespace controllers;
use \bloc\Application;

/**
 * Third Coast International Audio Festival Defaults
 */

class Task extends \bloc\controller
{
  public function __construct($request)
  {
  }

  public function CLIindex()
  {
    // show a list of methods.
    $reflection_class = new \ReflectionClass($this);
    
    $instance_class_name = get_class($this);
    $parent_class_name = $reflection_class->getParentClass()->name;
    $methods = ['instance' => [], 'parent' => []];
    
    foreach ($reflection_class->getMethods() as $method) {
      if (substr($method->name, 0, 3) == 'CLI') {
        $name = $method->getDeclaringClass()->name;
        if ($instance_class_name == $name) {
          $methods['instance'][] = substr($method->name, 3) . "\n";
        }
        if ($parent_class_name == $name) {
          $methods['parent'][] = substr($method->name, 3) . "\n";
        }
      }
    }
    
    echo "Available Methods in {$instance_class_name}\n";
    
    
    print_r($methods);
    
  }
  
  public function CLIcompress($file)
  {
    $text = file_get_contents(PATH . $file);
    $compressed = gzencode($text, 3);
        
    file_put_contents(PATH . substr($file, 0, -4), $compressed, LOCK_EX);
  }
  
  public function CLILogout()
  {
    if (unlink("/tmp/curlCookies.txt")) {
      echo "\nGoodbye!\n";
    }
    
  }
  
  public function CLILogin($xml)
  {
    $postdata = [];
    
    $xml = new \SimpleXMLElement($xml);
    $xml->registerXPathNamespace('xmlns', "http://www.w3.org/1999/xhtml");

    echo "\n" .(string)$xml->xpath('//xmlns:legend')[0] . "\n";
    $inputs = $xml->xpath('//xmlns:input');

    foreach ($inputs as $input) {
      
      if ((string)$input['id'] == 'name') {
        echo "\nPlease Enter your username: ";
        $input['value'] = trim(fgets(STDIN));
      }
      
      if ((string)$input['id'] == 'password') {
        echo "\nPlease Enter your password: ";
        $input['value'] = trim(fgets(STDIN));
      }
      
      $postdata[(string)$input['name']] = (string)$input['value'];
    }
    
    $url = 'http://local.thirdcoastfestival.org' . $xml->xpath('//xmlns:form')[0]['action'];

      
    $handle = curl_init();
 
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($handle, CURLOPT_AUTOREFERER,    true);
    curl_setopt($handle, CURLOPT_COOKIEFILE, "/tmp/curlCookies.txt");
    curl_setopt($handle, CURLOPT_COOKIEJAR, "/tmp/curlCookies.txt");
    
    $result = curl_exec($handle);
    $info   = curl_getinfo($handle);
    curl_close($handle);
    if ($info['http_code'] == 401) {
      $result = $this->CLILogin($result);
    }
    
    return $result;
  }
  
  public function CLIaws()
  {
    $client = \Aws\S3\S3Client::factory(['profile' => 'TCIAF']);
    $result = $client->listObjects([
        'Bucket' => '3rdcoast-features',
        'MaxKeys' => 2,
        'Marker' => 'mp3s/1000/We_Believe_We_Are_Invincible.mp3',
    ]);
    print($result);
    // foreach ($result['Buckets'] as $bucket) {
    //   print_r($bucket);
    //     // Each Bucket value will contain a Name and CreationDate
    //     echo "{$bucket['Name']} - {$bucket['CreationDate']}\n";
    // }
  }

}