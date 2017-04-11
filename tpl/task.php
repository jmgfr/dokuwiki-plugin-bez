<?php $template['no_edit'] = true ?>

<?php if (isset($template['issue'])): ?>
    <div id="bez_hidden_issue">
            <?php include 'issue_box.php' ?>
        <?php if (isset($template['commcause'])): ?>
        <div class="bez_comments" style="display: block; margin-bottom: 10px">
            <?php include 'commcause_box.php' ?>
        </div>
        <?php else: ?>
            <br />
        <?php endif ?>
    </div>

    <a href="#" id="bez_show_issue">
        <?php echo $bezlang['show_issue'] ?>
    </a>

<?php endif ?>

<?php $template['no_edit'] = false ?>

<?php if (	$template['action'] === 'task_edit' &&
            $template['tid'] === $template['task']->id): ?>
    <?php include 'task_form.php' ?>
<?php else: ?>
    <?php include 'task_box.php' ?>
<?php endif ?>

