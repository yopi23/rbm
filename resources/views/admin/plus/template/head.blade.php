<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- data table --}}
    {{-- <link href="{{asset('DataTables/datatables.min.css')}}"> --}}

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>

    <title>Document</title>
    <style>
        body {
            background-color: #dadada;
        }

        .sidebar {
            position: fixed;
            top: 50px;
            left: -280px;
            /* Sidebar dimulai dari posisi tersembunyi */
            width: 280px;
            height: 100%;
            background-color: #dadada;
            padding: 20px;
            z-index: 1000;
            transition: left 0.3s;
            /* Animasi perpindahan sidebar */
        }

        .sidebar.active {
            left: 0;
            /* Sidebar muncul saat aktif */
        }

        .card-tr {
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.5);
            /* box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2); */
            border-radius: 8px;
        }

        .main {
            margin-left: 10px;
            padding: 15px;
            width: 100%;
            transition: margin-left 0.4s;
            /* Animasi perpindahan konten utama */
        }

        .main.active {
            /* margin-left: 330px; */
            width: calc(100% - 250px);
            /* Margin kiri saat sidebar aktif */
        }

        .sidebar-toggle {
            left: 0;
            padding: 10px;
            /* background-color: #00a349; */
            color: white;
            cursor: pointer;
            border-radius: 8px;
        }

        .sidebar-toggle:hover {
            background-color: #00a349;
            /* Warna latar belakang saat dihover */
        }

        .nav-link {
            font-weight: bold;
            cursor: pointer;
            /* Mengubah tampilan kursor saat mouse di atasnya */
        }

        .nav-link a {
            text-decoration: none;
            /* Menghilangkan garis bawah */
            color: #333333;
            /* Mewarisi warna teks dari parent (.nav-link) */
            cursor: pointer;
            /* Mengubah tampilan kursor saat mouse di atasnya */
        }

        .nav-link a:link,
        .nav-link a:visited {
            color: #333333;
            /* Mewarisi warna teks dari parent (.nav-link) */
        }

        .nav-link a:hover {
            color: #333333;
            /* Mewarisi warna teks dari parent (.nav-link) */
        }


        /* CSS untuk layar kecil */
        @media (max-width: 768px) {
            .sidebar {
                left: 0;
                z-index: 1000;
            }

            .sidebar.active {
                left: -320px;
                z-index: 1000;
            }

            .main {
                width: 100%;
            }

            .main.active {
                width: calc(100%);
            }

            /* .sidebar-toggle {
                left: 320px;
            } */
        }

        .card-success.card-outline {
            border-top: 3px solid #28a745;
        }
    </style>
</head>

<body>
