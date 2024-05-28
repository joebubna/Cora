<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?= $title; ?></title>

    <!-- Bootstrap -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet'  type='text/css'>
    <link href="/resources/lib/bootstrap3/css/bootstrap.min.css" rel="stylesheet">
    <link href="/resources/lib/bootstrap3/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="/resources/app/css/main.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
      
    <!-- Navigation -->
    <nav id="topMenu" class="navbar navbar-default navbar-fixed-top" role="navigation">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#ifuel_menu_nav">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#">Cora Framework</a>
            </div>

            <?= $navbar; ?>
        </div>
    </nav>

    <div class="container-fluid">
        <?= $content; ?>
    </div>
    
    <footer class="container-fluid">
        <div class="wrapper">
            <p class="text-center">
                If you like Cora, please star us on Github (<a href="https://github.com/joebubna/Cora">https://github.com/joebubna/Cora</a>).
            </p>
            
            <div class="bottom text-right">
                <p class="pull-left">
                    <a href="https://github.com/joebubna/Cora/blob/master/LICENSE">MIT License</a>
                </p>
                
                <p class="">
                    Fun-fact: Cora is the brainchild of Josiah Bubna<br> and named after his daughter.
                </p>

                
            </div>
        </div>
    </footer>


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="/resources/lib/jquery/js/jquery-3.1.0.min.js"></script>
    
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="/resources/lib/bootstrap3/js/bootstrap.min.js"></script>
</body>
</html>
