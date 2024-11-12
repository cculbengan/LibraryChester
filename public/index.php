<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require '../src/vendor/autoload.php';
//$app = new \Slim\App;
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true,  
    ]
]);


$app->post('/user/register', function (Request $request, Response $response, array $args)
{
    $data=json_decode($request->getBody());
    $uname=$data->username ;
    $pass=$data->password ;
    $servername="localhost" ;
    $password="";
    $username="root";
    
    $dbname="library";

    try{
        $conn = new PDO("mysql:host=$servername; dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "INSERT INTO users (username, password) VALUES('". $uname."','".hash('sha256',$pass)."')";
        $conn->exec($sql);
        $response->getBody()->write(json_encode(array("status"=>"success","data"=>null,)));

    }catch(PDOException$e){
        $response->getBody()->write(json_encode(array("status"=>"fail","data"=>array("title"=>$e->getMessage()))));
    }
    $conn=null;
    return $response;
}); 

$app->post('/user/auth', function (Request $request, Response $response, array $args)
{   error_reporting(E_ALL);
    $data=json_decode($request->getBody());
    $uname=$data->username ;
    $pass=$data->password ;
    $servername="localhost" ;
    $password="";
    $username="root";
    $dbname="library";

    try{
        $conn = new PDO("mysql:host=$servername; dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM users WHERE username = '".$uname."' AND password='".hash('SHA256',$pass)."'";
        $stmt=$conn->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $data=$stmt->fetchAll();

        if(count($data)==1){
            $userid = $data[0]['userid'];
            $key='chesterthegreat';
            $iat=time();
            $payload=[
                'iss'=> 'http://library.org',
                'aud'=>'http://library.com',
                'iat'=> $iat, 
                'exp'=> $iat + 3600,
                'data'=>array(
                    "userid"=>$data[0]['userid'])
                ];
                
                $jwt=JWT::encode($payload, $key, 'HS256');
                $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':userid' => $userid]);

                if ($stmt->rowCount() > 0) {
                
                    $sql = "UPDATE used_tokens SET token = :token WHERE userid = :userid";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':token' => $jwt,
                        ':userid' => $userid
                    ]);
                } else {
                    
                    $sql = "INSERT INTO used_tokens (token, userid) VALUES (:token, :userid)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':token' => $jwt,
                        ':userid' => $userid
                    ]);
                }
               
                    $response->getBody()->write(
                json_encode(array("status"=>"success","token"=>$jwt,"data"=>null, )));    
            }
        else{
        $response->getBody()->write(
            json_encode(array("status"=>"fail","data"=>array("title"=>"authentication failed")))
        );
    }
    }catch(PDOException $e){
        $response->getBody()->write(json_encode(array("status"=>"fail","data"=>array("title"=>$e->getMessage()))));
    }
    $conn=null;
    return $response;
}); 


$app->put('/user/update', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $newUsername = $data->new_username;
    $newPassword = $data->new_password;
    $jwt = $data->token;
    $userid = $data->userid;
    $servername = "localhost";
    $dbusername = "root";
    $dbpassword = "";
    $dbname = "library";
    $key = 'chesterthegreat';

    try {
        
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        
       
        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        

        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        } else
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
        
            
        
        $sql = "UPDATE users SET username = :username, password = :password WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'username' => $newUsername,
            'password' => hash('sha256', $newPassword),
            'userid' => $userid
        ]);

        if ($stmt->rowCount() > 0) {
            $key='chesterthegreat';
                $iat=time();
                $payload=[
                    'iss'=> 'http://library.org',
                    'aud'=>'http://library.com',
                    'iat'=> $iat, 
                    'exp'=> $iat + 3600,
                    'data'=>array(
                        "userid"=>$userid)
                ];

                $jwt=JWT::encode($payload, $key, 'HS256');
                $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'token' => $jwt,
                    'userid'=> $userid   
            ]);
            $response->getBody()->write(json_encode(array("status" => "success", "data" => "User updated successfully","newToken" =>$jwt)));
        } else {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => "No changes made")));
        }}
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid Token, Please Login Again"))));
    }

    $conn = null;
    return $response;
});

$app->delete('/user/delete', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $jwt = $data->token;
    $userid = $data->userid;
    $servername = "localhost";
    $dbusername = "root";
    $dbpassword = "";
    $dbname = "library";
    $key = 'chesterthegreat';

    try {
       
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        

        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        } else
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{

        $sql = "DELETE FROM users WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['userid' => $userid]);

        if ($stmt->rowCount() > 0) {
            $key='chesterthegreat';
            $iat=time();
            $payload=[
                'iss'=> 'http://library.org',
                'aud'=>'http://library.com',
                'iat'=> $iat, 
                'exp'=> $iat + 3600,
                'data'=>array(
                    "userid"=>$userid)
            ];

            $jwt=JWT::encode($payload, $key, 'HS256');
            $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'token' => $jwt,
                'userid'=> $userid   
        ]);
            $response->getBody()->write(json_encode(array("status" => "success", "data" => "User deleted successfully","newToken" =>$jwt)));
        } else {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => "No user found to delete")));
        }}
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid Token, Please Login Again"))));
    }

    $conn = null;
    return $response;
});

$app->post('/read/user', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $jwt = $data->token;  
    $userid = $data->userid;
    $servername = "localhost";
    $dbusername = "root";
    $dbpassword = "";
    $dbname = "library";
    $key = 'chesterthegreat';

    try {
        
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        

        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        } else
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
        

        $sql = "SELECT userid, username FROM users WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['userid' => $userid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $key='chesterthegreat';
                $iat=time();
                $payload=[
                    'iss'=> 'http://library.org',
                    'aud'=>'http://library.com',
                    'iat'=> $iat, 
                    'exp'=> $iat + 3600,
                    'data'=>array(
                        "userid"=>$userid)
                ];

                $jwt=JWT::encode($payload, $key, 'HS256');
                $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'token' => $jwt,
                    'userid'=> $userid   
            ]);
            $response->getBody()->write(json_encode(array("status" => "success", "data" => $user, "newToken" =>$jwt)));
        } else {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => "User not found")));
        }}
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid Token, Please Login Again"))));
    }

    $conn = null;
    return $response;
});



$app->post('/add/books', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $loc = $data->loc;
    $author = $data->author;
    $title = $data->title;
    $userid = $data->userid;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $key = 'chesterthegreat';
    $jwt = $data->token;

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
        
        try {
            
            $sql = "SELECT authorid FROM authors WHERE name = :author";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['author' => $author]);
            $existingAuthor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingAuthor) {
                // If author does not exist, insert the new author
                $sql = "INSERT INTO authors (name) VALUES (:author)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['author' => $author]);
                $authorid = $conn->lastInsertId();
            } else {
                $authorid = $existingAuthor['authorid'];
            }

            $sql = "SELECT locid FROM location WHERE loc = :loc";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['loc' => $loc]);
            $existingLocation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingLocation) {
                $sql = "INSERT INTO location (loc) VALUES (:loc)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['loc' => $loc]);
                $locid = $conn->lastInsertId();
            } else {
                $locid = $existingLocation['locid'];
            }

            $sql = "SELECT COUNT(*) FROM books WHERE title = :title AND authorid = :authorid";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['title' => $title, 'authorid' => $authorid]);
            $existingBookCount = $stmt->fetchColumn();

            if ($existingBookCount > 0) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Book with the same title and author already exists"))));
            } else {
            
                $sql = "INSERT INTO books (title, authorid, locid) VALUES (:title, :authorid, :locid)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['title' => $title, 'authorid' => $authorid, 'locid' => $locid]);
                $bookid = $conn->lastInsertId();
                
               
                $sql = "INSERT INTO books_author (bookid, authorid, locid) VALUES (:bookid, :authorid, :locid)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['bookid' => $bookid, 'authorid' => $authorid, 'locid' => $locid]);
                
                $key='chesterthegreat';
                $iat=time();
                $payload=[
                    'iss'=> 'http://library.org',
                    'aud'=>'http://library.com',
                    'iat'=> $iat, 
                    'exp'=> $iat + 3600,
                    'data'=>array(
                        "userid"=>$userid)
                ];

                $jwt=JWT::encode($payload, $key, 'HS256');
                $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'token' => $jwt,
                    'userid'=> $userid   
            ]);
                $response->getBody()->write(json_encode(array("status" => "success", "data" => null, "newToken" =>$jwt)));
            }

        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
        }}
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Token Expired, Please Relogin"))));
    }

    $conn = null;
    return $response;
});


$app->post('/add/author', function (Request $request, Response $response, array $args)
{
    $data = json_decode($request->getBody());
    $authorname = $data->authorname;
    $userid = $data->userid;
    $servername = "localhost";
    $password = "";
    $username = "root";
    $dbname = "library";

    $key = 'chesterthegreat';
    $jwt = $data->token;
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
        

        try {
            $conn = new PDO("mysql:host=$servername; dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT COUNT(*) FROM authors WHERE name = :authorname";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['authorname' => $authorname]);
            $authorExists = $stmt->fetchColumn();

            if ($authorExists > 0) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Author already exists"))));
            } else {
                $sql = "INSERT INTO authors (name) VALUES (:authorname)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['authorname' => $authorname]);

                $key='chesterthegreat';
                $iat=time();
                $payload=[
                    'iss'=> 'http://library.org',
                    'aud'=>'http://library.com',
                    'iat'=> $iat, 
                    'exp'=> $iat + 3600,
                    'data'=>array(
                        "userid"=>$userid)
                ];

                $jwt=JWT::encode($payload, $key, 'HS256');
                $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'token' => $jwt,
                    'userid'=> $userid   
            ]);

                $response->getBody()->write(json_encode(array("status" => "success", "data" => null, "newToken" =>$jwt)));
            }

        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
        }}
    } catch (Exception $e) {
         
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Token Expired or Invalid"))));
    }
    
    $conn = null;
    return $response;
});

$app->post('/add/location', function (Request $request, Response $response, array $args)
{
    $data = json_decode($request->getBody());
    $location = $data->location;
    $userid = $data->userid;
    $servername = "localhost";
    $password = "";
    $username = "root";
    $dbname = "library";

    $key = 'chesterthegreat';
    $jwt = $data->token;

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{

        try {
            $conn = new PDO("mysql:host=$servername; dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT COUNT(*) FROM location WHERE loc = :location";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['location' => $location]);
            $locationExists = $stmt->fetchColumn();

            if ($locationExists > 0) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Location already exists"))));
            } else {
                $sql = "INSERT INTO location (loc) VALUES (:location)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['location' => $location]);

                $iat=time();
                $payload=[
                    'iss'=> 'http://library.org',
                    'aud'=>'http://library.com',
                    'iat'=> $iat, 
                    'exp'=> $iat + 3600,
                    'data'=>array(
                        "userid"=>$userid)
                ];

                $jwt=JWT::encode($payload, $key, 'HS256');
                $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'token' => $jwt,
                    'userid'=> $userid   
            ]);

                $response->getBody()->write(json_encode(array("status" => "success", "data" => null, "newToken" =>$jwt)));
            }

        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
        }}
    } catch (Exception $e) {
         
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Token Expired, Please Relogin"))));
    }
    
    $conn = null;
    return $response;
});

$app->get('/read/allbooks', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    $data = json_decode($request->getBody());
    $userid = $data->userid;
    $key = 'chesterthegreat';
    $jwt = $data->token;

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
        
        $sql = "SELECT * FROM books";
        $stmt = $conn->query($sql);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
        $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
    ]);

        $response->getBody()->write(json_encode(array("status"=>"success", "data"=>$books, "newToken" =>$jwt)));
        }
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }

    $conn = null;
    return $response;
});
$app->get('/read/books/{bookid}', function (Request $request, Response $response, array $args) {
    $bookid = $args['bookid'];
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    $data = json_decode($request->getBody());
    $userid = $data->userid;
    $key = 'chesterthegreat';
    $jwt = $data->token;



    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
        
        $sql = "SELECT * FROM books WHERE bookid = :bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $bookid]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($book) {
            $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
    ]);
            $response->getBody()->write(json_encode(array("status"=>"success", "data"=>$book, "newToken" =>$jwt)));
        } else {
            $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>"Book not found"))));
        }}
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }

    $conn = null;
    return $response;
});
$app->get('/read/allauthors', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    
    $data = json_decode($request->getBody());
    $userid = $data->userid;
    $key = 'chesterthegreat';
    $jwt = $data->token;

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
        
        $sql = "SELECT * FROM authors";
        $stmt = $conn->query($sql);
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
    ]);

        $response->getBody()->write(json_encode(array("status"=>"success", "data"=>$authors, "newToken" =>$jwt)));
        }
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }

    $conn = null;
    return $response;
});

$app->get('/read/authors/{authorid}', function (Request $request, Response $response, array $args) {
    $authorid = $args['authorid'];
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    $data = json_decode($request->getBody());
    $userid = $data->userid;
    $key = 'chesterthegreat';
    $jwt = $data->token;

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{

        $sql = "SELECT name as author_name, title as book_title, l.loc as book_location
                FROM books_author ba
                JOIN authors a ON a.authorid = ba.authorid
                JOIN books b ON b.bookid = ba.bookid
                JOIN location l ON l.locid = ba.locid
                WHERE a.authorid = :authorid";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute(['authorid' => $authorid]);
        $author = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($author) {
            $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
    ]);
            $response->getBody()->write(json_encode(array("status" => "success", "data" => $author, "newToken" =>$jwt)));
        } else {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("message" => "No books found for this author"))));
        }}
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("message" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});

$app->get('/read/location', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    $data = json_decode($request->getBody());
    $userid = $data->userid;
    $key = 'chesterthegreat';
    $jwt = $data->token;

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
        
        $sql = "SELECT * FROM location";
        $stmt = $conn->query($sql);
        $location = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
    ]);
        $response->getBody()->write(json_encode(array("status"=>"success", "data"=>$location, "newToken" =>$jwt)));
    }} catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }

    $conn = null;
    return $response;
});

$app->get('/read/location/{locid}', function (Request $request, Response $response, array $args) {
    $locid = $args['locid'];
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    $data = json_decode($request->getBody());
    $userid = $data->userid;
    $key = 'chesterthegreat';
    $jwt = $data->token;

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT loc as location_name, title as book_title, name as book_author
                FROM books_author ba
                JOIN location a ON a.locid = ba.locid
                JOIN books b ON b.bookid = ba.bookid
                JOIN authors l ON l.authorid = ba.authorid
                WHERE a.locid = :locid";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute(['locid' => $locid]);
        $loc = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($loc) {
            $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
    ]);
            $response->getBody()->write(json_encode(array("status" => "success", "data" => $loc, "newToken" =>$jwt)));
        } else {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("message" => "No books found for this author"))));
        }
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("message" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});

$app->put('/update/books/{bookid}', function (Request $request, Response $response, array $args) {
    $bookid = $args['bookid'];
    $data = json_decode($request->getBody());
    $title = $data->title;
    $author = $data->author;
    $loc = $data->loc;
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $userid = $data->userid;
    $key = 'chesterthegreat';
    $jwt = $data->token;
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{

        try {
            

            $sql = "SELECT authorid FROM authors WHERE name = :author";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['author' => $author]);
            $existingAuthor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingAuthor) {
                $sql = "INSERT INTO authors (name) VALUES (:author)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['author' => $author]);
                $authorid = $conn->lastInsertId();  
            } else {
                $authorid = $existingAuthor['authorid'];  
            }
            $sql = "SELECT locid FROM location WHERE loc = :loc";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['loc' => $loc]);
            $existingLocation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingLocation) {
                $sql = "INSERT INTO location (loc) VALUES (:loc)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['loc' => $loc]);
                $locid = $conn->lastInsertId();  
            } else {
                $locid = $existingLocation['locid'];  
            }

            $sql = "UPDATE books SET title = :title, authorid = :authorid, locid = :locid WHERE bookid = :bookid";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'title' => $title,
                'authorid' => $authorid,
                'locid' => $locid,
                'bookid' => $bookid
            ]);

            $key='chesterthegreat';
            $iat=time();
            $payload=[
                'iss'=> 'http://library.org',
                'aud'=>'http://library.com',
                'iat'=> $iat, 
                'exp'=> $iat + 3600,
                'data'=>array(
                    "userid"=>$userid)
            ];

            $jwt=JWT::encode($payload, $key, 'HS256');
            $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'token' => $jwt,
                'userid'=> $userid   
        ]);

            $response->getBody()->write(json_encode(array("status" => "success", "data" => null, "newToken" =>$jwt)));
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
        }}
    } catch (Exception $e) {
         
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Token Expired, Please Relogin"))));
    }

    $conn = null;
    return $response;
});

$app->put('/update/authors/{authorid}', function (Request $request, Response $response, array $args) {
    $authorid = $args['authorid'];
    $data = json_decode($request->getBody());
    $name = $data->name;
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $userid = $data->userid;
    $key = 'chesterthegreat';
    $jwt = $data->token;
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{

    try {
        
        
        $sql = "UPDATE authors SET name = :name WHERE authorid = :authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['name' => $name, 'authorid' => $authorid]);

        $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
    ]);

        $response->getBody()->write(json_encode(array("status"=>"success", "data"=>null, "newToken" =>$jwt)));
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }}
} catch (Exception $e) {
     
    $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Token Expired, Please Relogin"))));
}

    $conn = null;
    return $response;
});

$app->put('/update/location/{locid}', function (Request $request, Response $response, array $args) {
    $locid = $args['locid'];
    $data = json_decode($request->getBody());
    $loc = $data->loc;
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $userid = $data->userid;
    $key = 'chesterthegreat';
    $jwt = $data->token;
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{


    try {
        
        $sql = "UPDATE location SET loc = :loc WHERE locid = :locid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['loc' => $loc, 'locid' => $locid]);

        $iat=time();
                $payload=[
                    'iss'=> 'http://library.org',
                    'aud'=>'http://library.com',
                    'iat'=> $iat, 
                    'exp'=> $iat + 3600,
                    'data'=>array(
                        "userid"=>$userid)
                ];

                $jwt=JWT::encode($payload, $key, 'HS256');
                $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'token' => $jwt,
                    'userid'=> $userid   
            ]);

        $response->getBody()->write(json_encode(array("status"=>"success", "data"=>null, "newToken" =>$jwt)));
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }}
} catch (Exception $e) {
     
    $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Token Expired, Please Relogin"))));
}
$conn = null;
return $response;
});

$app->delete('/delete/books/{bookid}', function (Request $request, Response $response, array $args) {
    $bookid = $args['bookid'];
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $key ='chesterthegreat';
    $data=json_decode($request->getBody());
    $jwt=$data->token;
    $userid = $data->userid;
    try{
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
        
    try {
       
        $sql = "DELETE FROM books WHERE bookid = :bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $bookid]);

        $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
        ]);
        $response->getBody()->write(json_encode(array("status"=>"success", "data"=>null,"newToken" =>$jwt)));
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }}
}
catch(Exception $e){
    $response->getBody()->write(json_encode(array("status"=>"fail","data"=>array("title"=>"Token Expired, Please Relogin"))));
}

    $conn = null;
    return $response;
});
$app->delete('/delete/authors/{authorid}', function (Request $request, Response $response, array $args) {
    
    $authorid = $args['authorid'];
   
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $key ='chesterthegreat';
    $data=json_decode($request->getBody());
    $userid = $data->userid;
    $jwt=$data->token;
    try{
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
    try {
       
        $sql = "DELETE FROM authors WHERE authorid = :authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['authorid' => $authorid]);

        $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
        ]);
        $response->getBody()->write(json_encode(array("status"=>"success", "data"=>null, "newToken" =>$jwt)));
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }}
}
catch(Exception $e){
    $response->getBody()->write(json_encode(array("status"=>"fail","data"=>array("title"=>"Token Expired, Please Relogin"))));
}
    $conn = null;
    return $response;
});

$app->delete('/delete/location/{locid}', function (Request $request, Response $response, array $args) {
    $locid = $args['locid'];
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $key ='chesterthegreat';
    $data=json_decode($request->getBody());
    $jwt=$data->token;
    $userid = $data->userid;
    try{
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM used_tokens WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
            $stmt->execute(['userid' => $userid]);
        $userdata = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userdata){
            $response->getBody()->write(json_encode(array("status" => "no token", "data" => null)));
        }
        if ($userdata ['token'] != $jwt){
            $response->getBody()->write(json_encode(array("status" => "invalid token", "data" => null)));
        }else{
    try {
        
        
        $sql = "DELETE FROM location WHERE locid = :locid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['locid' => $locid]);

        $iat=time();
        $payload=[
            'iss'=> 'http://library.org',
            'aud'=>'http://library.com',
            'iat'=> $iat, 
            'exp'=> $iat + 3600,
            'data'=>array(
                "userid"=>$userid)
        ];

        $jwt=JWT::encode($payload, $key, 'HS256');
        $sql = "UPDATE used_tokens SET token = :token  WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'token' => $jwt,
            'userid'=> $userid   
    ]);

        $response->getBody()->write(json_encode(array("status"=>"success", "data"=>null, "newToken" =>$jwt)));
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }}
}
catch(Exception $e){
    $response->getBody()->write(json_encode(array("status"=>"fail","data"=>array("title"=>"Token Expired, Please Relogin"))));
}

    $conn = null;
    return $response;
});


$app->run();

//go to https://github.com/firebase/php-jwt
//C:\xampp\htdocs\security\src>composer require firebase/php-jwt on cmd
