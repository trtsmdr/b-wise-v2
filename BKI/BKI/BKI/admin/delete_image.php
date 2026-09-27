<?php
    require 'koneksi.php';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $image = $_POST['image'];
        $id    = $_POST['id'];

        $file_path = 'img/' . $image;
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $query  = "SELECT gambar FROM planning WHERE id = ?";
        $stmt   = $koneksi->prepare($query);
        $stmt   ->bind_param('i', $id);
        $stmt   ->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $images = explode(',', $row['gambar']);
        $images = array_filter($images, function($img) use ($image) {
            return trim($img) !== trim($image);
        });
        $new_gambar = implode(',', $images);

        $query = "UPDATE planning SET gambar = ? WHERE id = ?";
        $stmt  = $koneksi->prepare($query);
        $stmt  ->bind_param('si', $new_gambar, $id);
        
        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'failure';
        }
    }
?>