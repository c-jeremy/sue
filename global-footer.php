<?php
(isset($include_src)) || die("This footer file requires a main file as the main web page.");
?>
<style>
    footer {
        text-align: center;
        margin-top: 48px;
        color: #6b7280;
    }
    footer a {
        text-decoration: underline;
        color:#6b7280;
    }
</style>

<footer><small>
    &copy; Copyright <?php echo date("Y"); ?> Sue. Made by Cao Zhiming & Tang Ziyan.<br>
    <a href="/terms.php">Terms</a>&nbsp;|&nbsp;<a href="/privacy.php">Privacy</a>&nbsp;
    |&nbsp;Proudly Partnered with <a href="//GeekerLStar.com?from=sue_footer">Geeker LStar</a>.
    <?php if($_SESSION["user_id"] % 520218 < 3){ ?>
    <br>
    <a href="/facecheckin.php?nsbsts=bushia"><strong>Face Check in System &rarr;</strong></a>
    <?php } ?>
    </small>
    <div><br></div>
    <script src="//123.56.160.48:520/tracker.js?ver=0.812"></script>
</footer>