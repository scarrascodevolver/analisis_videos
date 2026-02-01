  <?php
  if ($_POST) {
      echo '<h3>POST Data:</h3>';
      print_r($_POST);
      echo '<h3>FILES Data:</h3>';
      print_r($_FILES);
      echo '<h3>Errors:</h3>';
      if (isset($_FILES['avatar']['error'])) {
          echo 'Error code: '.$_FILES['avatar']['error'];
      }
  }
?>
  <form method="post" enctype="multipart/form-data">
      <input type="file" name="avatar" accept="image/*" required>
      <button type="submit">Test Upload</button>
  </form>
