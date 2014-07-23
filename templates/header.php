<!DOCTYPE html>

<html>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <meta name="viewport" content="initial-scale=1.0"/>
    <!--<meta name="viewport" content="target-densitydpi=medium-dpi" />-->

    <link rel="icon" type="image/ico" href="/favicon.ico" />

    <?php if (!$mobile): ?>

    <link rel="stylesheet" type="text/css" href="<?php echo $template_url ?>css/stylesheets/main.css">
    <link rel="stylesheet" href="<?php echo $template_url ?>font-awesome/css/font-awesome.min.css">

    <?php foreach($js as $j): ?>
    <script type="text/javascript" src="<?php echo $j ?>"></script>
    <?php endforeach; ?>

    <?php if ($sb): ?>
    <script type="text/javascript" src="<?php echo $template_url ?>js/pages/sidebar.js"></script>
    <?php endif; ?>

    <script type="text/javascript">
        $(function() {
            $('.debug').click(function() {
                $(this).next('pre').slideToggle()
            })
        })

    </script>
    <?php endif; // !$mobile ?>

    <?php echo $header ?>

    <title><?php echo $title ?></title>

</head>

<body>

<?php if (!$mobile): ?>
<div id="wrapper">
<?php endif; ?>

<?php if ($hf): ?>
<div id="header" class="clearfix">
    <ul id="menu">
        <?php if ($this->staff): ?>

        <?php foreach($this->ptype->ext_admin as $eam): ?>
        <li><a href="<?php echo $eam[0] ?>"><i class="fa fa-<?php echo $eam[2] ?> fa-2x"></i> <span class="icon-label"><?php echo $eam[1] ?></span></a></li>
        <?php endforeach; ?>

        <!--
        <li class="selected"><a href="/robot" title="Robot Stats"><i class="fa fa-android fa-2x"></i> <span class="icon-label">Robot Stats</span></a></li>-->


        <li><a href="/fault" title="Fault Report"><i class="fa fa-tasks fa-2x"></i> <span class="icon-label">Fault Report</span></a></li>
        <?php endif; ?>
        <li><a href="/logout" title="Logout"><i class="fa fa-sign-out fa-2x"></i> <span class="icon-label">Logout</span></a></li>
    </ul>

    <a class="icon pull"><i class="fa fa-bars fa-2x"></i> <span class="icon-label">Menu</span></a>
    <a class="icon" href="/"><i class="fa fa-home fa-2x"></i> <span class="icon-label">Home</span></a>
    <?php if ($this->staff): ?>
        <a class="icon" href="/cal"><i class="fa fa-calendar fa-2x"></i> <span class="icon-label">Calendar</span></a>
    <?php endif; ?>

    <span class="search-mobile">
        <input type="text" class="search-mobile" placeholder="&#xf002;" />
    </span>

    <ul id="navigation">
        <?php foreach ($nav['p'] as $i => $p): ?>
            <?php if ($nav['l'][$i]): ?>
            <li><a href="<?php echo $nav['l'][$i] ?>"><?php echo $p ?></a></li>
            <?php else: ?>
            <li><?php echo $p ?></li>
            <?php endif ?>
        <?php endforeach ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($sb): ?>
    <?php include($template_path.'sidebar.php'); ?>
<?php endif; ?>


<?php if (!$mobile): ?>
<div class="cont_wrap">
<div id="container">

    <div class="content">

    <p class="message notify">
    This is the new interface to ISPyB at Diamond. If you want to escape to the old version click <a href="https://oldspyb.diamond.ac.uk">here</a>
    </p>

<?php endif; ?>



