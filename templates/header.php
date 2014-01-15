<!DOCTYPE html>

<html>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <meta name="viewport" content="initial-scale=1.0"/>
    <!--<meta name="viewport" content="target-densitydpi=medium-dpi" />-->

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
        <li class="selected"><a href="/robot" title="Robot Stats"><i class="fa fa-gears fa-2x"></i> <span class="icon-label">Robot Stats</span></a></li>
        <li><a href="/fault" title="Fault Report"><i class="fa fa-tasks fa-2x"></i> <span class="icon-label">Fault Report</span></a></li>
        <?php endif; ?>
    </ul>

    <a class="icon pull"><i class="fa fa-bars fa-2x"></i> <span class="icon-label">Menu</span></a>
    <a class="icon" href="/"><i class="fa fa-home fa-2x"></i> <span class="icon-label">Home</span></a>
    <?php if ($this->staff): ?>
        <a class="icon" href="/cal"><i class="fa fa-calendar fa-2x"></i> <span class="icon-label">Calendar</span></a></li>
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

<?php endif; ?>



