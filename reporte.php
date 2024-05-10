<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Radio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <style>
    body {
      background-color: white;
      /* Fondo gris */
    }

    /* Estilo para el fondo del botón del carrusel */
    .carousel-control-prev,
    .carousel-control-next {
      background-color: blue;
      /* Fondo azul */
      width: 40px;
      /* Ancho del botón */
      height: 40px;
      /* Altura del botón */
      border-radius: 50%;
      /* Hacer el botón circular */
    }

    /* Estilo para los íconos del carrusel */
    .carousel-control-prev-icon,
    .carousel-control-next-icon {
      color: white;
      /* Color blanco para los íconos */
      font-size: 20px;
      /* Tamaño del ícono */
    }
  </style>
</head>

<body>
  <!--buscador y primer grafico -->
  <div class="container mt-5">
    <div class="row">
      <div class="col-md-6 offset-md-3">
        <h2 class="text-center mb-4">Dashboard Radio</h2>
        <form method="post" action="<?php echo $PHP_SELF; ?>">
          <div class="input-group mb-3">

            <!--Seleccionar radio -->
            <select class="form-select" id="searchType" name="montajenames" id="montajenames">
              <?php
              // Ejecutar el comando shell para obtener los medios
              $medios = shell_exec("cat ./access.log | awk '{ print $7 }' | sed s/\"\/\"//g | sed s/\";\"//g | grep -v \"\.\" | grep -v admin | sort | uniq");

              // Dividir la salida en líneas y eliminar espacios en blanco adicionales
              $list = array_filter(explode("\n", $medios), 'trim');

              // Contar la cantidad de elementos en la lista
              $montajes = count($list);
              ?>
              <?php
              // Iterar sobre la lista de medios para generar las opciones
              foreach ($list as $item) {
                ?>
                <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
              <?php } ?>

            </select>

            <!--Seleccionar calendario -->
            <div class="input-group-text" id="calendarIcon">
              <i class="bi bi-calendar"></i>
            </div>
            <input type="date" class="form-control" id="datepicker" placeholder="Seleccionar fecha" id="fecha"
              name="fecha">
            <!--Seleccionar boton -->
            <button class="btn btn-primary" id="searchButton" type="submit">Buscar</button>
          </div>
        </form>
        <div class="row mt">
          <div id="alerta" class="alert alert-primary" role="alert">
            <?php
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
              // Verificar si se ha seleccionado un punto de montaje
              if (isset($_POST['montajenames'])) {
                $selected_mnt = $_POST['montajenames'];
                echo "Ha seleccionado: <b>$selected_mnt</b><br>";
              } else {
                echo "No se ha seleccionado ningún punto de montaje.<br>";
              }
              // Verificar si se ha seleccionado una fecha
              if (isset($_POST['fecha'])) {
                $fecha = date("d/M/Y", strtotime($_POST['fecha']));
                echo "La fecha seleccionada es: <b>$fecha</b><br>";
              } else {
                echo "No se ha seleccionado ninguna fecha.<br>";
              }
            }
            ?>
          </div>
        </div>
      </div>
    </div>
    <div class="row mt-4">
      <div class="col-md-6 offset-md-3">
        <div class="alert alert-secondary" role="alert">
          <!-- <h3 class="text-center"> Gráficos de mapa de árbol</h3> -->
          <div>
            <div>
              <?php
              // Ejecutar el comando para obtener las direcciones IP y los países
              $comando = "for i in `cat ./access.log* ./access.log.old | grep $selected_mnt | grep $fecha | awk '{ print \$1 }' | sort | uniq`; do echo \$i; geoiplookup \$i; done | awk '{ print \$4 \$5 }' | sort | grep -v IPAddress | uniq -c | sort -rg";

              // Ejecutar el comando y almacenar el resultado
              $resultado = shell_exec($comando);

              // Procesar el resultado para preparar los datos del gráfico
              $datos_grafico = [];
              foreach (explode("\n", $resultado) as $linea) {
                if (!empty($linea)) {
                  $partes = explode(" ", trim($linea));
                  $pais = end($partes);
                  $conteo = prev($partes);
                  $datos_grafico[] = [
                    'x' => $pais,
                    'y' => intval($conteo)
                  ];
                }
              }
              // Configuración del gráfico
              $opciones_grafico = [
                'series' => [
                  ['data' => $datos_grafico]
                ],
                'legend' => [
                  'show' => false
                ],
                'chart' => [
                  'height' => 350,
                  'type' => 'treemap'
                ],
                'title' => [
                  'text' => 'Distribución Geográfica por Punto de Acceso',
                  'align' => 'center'
                ],
                'colors' => [
                  '#351c75',
                  '#16537E',
                  '#3B93A5',
                  '#F7B844',
                  '#ADD8C7',
                  '#EC3C65',
                  '#CDD7B6',
                  '#C1F666',
                  '#D43F97',
                  '#1E5D8C',
                  '#421243',
                  '#7F94B0',
                  '#EF6537',
                  '#C0ADDB'
                ],
                'plotOptions' => [
                  'treemap' => [
                    'distributed' => true,
                    'enableShades' => false
                  ]
                ]
              ];
              // Convertir opciones a JSON
              $opciones_json = json_encode($opciones_grafico);
              ?>
              <div id="chartGeografia"></div>

              <script>
                var options = <?php echo $opciones_json; ?>;
                var chart = new ApexCharts(document.querySelector("#chartGeografia"), options);
                chart.render();
              </script>

            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <!--Carrusel -->
  <div id="carouselExampleControlsNoTouching" class="carousel slide" data-bs-touch="false" data-bs-interval="false">
    <div class="carousel-inner">
      <!-- Diapositiva 1 -->
      <div class="carousel-item active">
        <div class="row mt-4 justify-content-center">
          <div class="col-md-6">
            <div class="alert alert-secondary" role="alert">
              <!-- Primer gráfico -->
              <?php
              // Ejecutar el comando para filtrar y contar conexiones por hora
              $comando = "cat ./access.log* ./access.log.old | grep '$selected_mnt' | grep $fecha | awk '{ print substr(\$4, 14, 2) }' | sort | uniq -c";

              // Ejecutar el comando y almacenar el resultado
              $resultado = shell_exec($comando);

              // Procesar el resultado para crear los datos del gráfico
              $datos_grafico = [];
              foreach (explode("\n", $resultado) as $linea) {
                if (!empty($linea)) {
                  list($conteo, $hora) = explode(" ", trim($linea));
                  $datos_grafico[] = [
                    'hora' => intval($hora),
                    'conteo' => intval($conteo)
                  ];
                }
              }

              // Ordenar los datos por hora
              usort($datos_grafico, function ($a, $b) {
                return $a['hora'] - $b['hora'];
              });

              // Convertir datos a formato adecuado para el gráfico
              $horas = [];
              $conexiones = [];
              foreach ($datos_grafico as $dato) {
                $horas[] = sprintf("%02d", $dato['hora']) . ":00";
                $conexiones[] = $dato['conteo'];
              }

              // Configuración del gráfico
              $opciones_grafico = [
                'series' => [
                  [
                    'data' => $conexiones
                  ]
                ],
                'chart' => [
                  'height' => 350,
                  'type' => 'bar',
                  'events' => [
                    'click' => 'function(chart, w, e) { }'
                  ]
                ],

                'colors' => [

                  '#D4AC0D',
                  '#8E44AD',
                  '#F39C12',
                  '#3498DB',


                ],
                'plotOptions' => [
                  'bar' => [
                    'columnWidth' => '45%',
                    'distributed' => true,
                  ]
                ],
                'dataLabels' => [
                  'enabled' => false
                ],
                'legend' => [
                  'show' => false
                ],
                'title' => [
                  'text' => 'Actividad del Punto de Montaje por Horario',
                  'align' => 'center'
                ],
                'xaxis' => [
                  'categories' => $horas,
                  'labels' => [
                    'style' => [
                      'colors' => '#008FFB',
                      'fontSize' => '12px'
                    ]
                  ]
                ]
              ];

              // Convertir opciones a JSON
              $opciones_json = json_encode($opciones_grafico);
              ?>

              <div id="chartBarras"></div>
              <script>
                var options = <?php echo $opciones_json; ?>;
                var chart = new ApexCharts(document.querySelector("#chartBarras"), options);
                chart.render();
              </script>
            </div>
          </div>
        </div>
      </div>
      <!-- Diapositiva 2 -->
      <div class="carousel-item">
        <div class="row mt-4 justify-content-center">
          <div class="col-md-6">
            <div class="alert alert-secondary" role="alert">
              <!-- Segundo gráfico -->
              <?php
              // Función para obtener el sistema operativo desde el User-Agent
              function obtenerSistemaOperativo($user_agent)
              {
                $sistemas_operativos = [
                  'Windows' => 'Windows',
                  'Android' => 'Android',
                  'Mac' => 'MacOS',
                  'iOS' => 'iOS'
                  // Agregar más sistemas operativos si es necesario
                ];

                foreach ($sistemas_operativos as $so => $nombre_so) {
                  if (stripos($user_agent, $so) !== false) {
                    return $nombre_so;
                  }
                }

                return null;
              }

              // Ejecutar el comando para obtener los User-Agent
              $comando = "cat ./access.log* ./access.log.old | grep '$selected_mnt' | grep $fecha | awk -F '\"' '{print \$6}'";

              // Ejecutar el comando y almacenar el resultado
              $resultado = shell_exec($comando);

              // Procesar el resultado para extraer los sistemas operativos únicos
              $user_agents = explode("\n", trim($resultado));
              $so_unicos = [];
              foreach ($user_agents as $user_agent) {
                // Extraer el sistema operativo del User-Agent
                $sistema_operativo = obtenerSistemaOperativo($user_agent);
                if (!empty($sistema_operativo)) {
                  $so_unicos[] = $sistema_operativo;
                }
              }

              // Contar el número de ocurrencias de cada sistema operativo
              $so_contados = array_count_values($so_unicos);

              // Preparar los datos para el gráfico de polarArea
              $etiquetas = [];
              $conteos = [];
              foreach ($so_contados as $so => $conteo) {
                $etiquetas[] = $so;
                $conteos[] = $conteo;
              }

              // Configuración del gráfico de ApexCharts
              $opciones_grafico = [
                'series' => $conteos,
                'labels' => $etiquetas,
                'chart' => [
                  'type' => 'polarArea',
                ],
                'stroke' => [
                  'colors' => ['#fff']
                ],
                'fill' => [
                  'opacity' => 0.8
                ],
                'title' => [
                  'text' => 'Sistemas Operativos más utilizados para el Punto de Montaje',
                  'align' => 'center'
                ],
                'responsive' => [
                  [
                    'breakpoint' => 480,
                    'options' => [
                      'chart' => [
                        'width' => 200
                      ],
                      'legend' => [
                        'position' => 'bottom'
                      ]
                    ]
                  ]
                ]
              ];

              // Convertir opciones a JSON
              $opciones_json = json_encode($opciones_grafico);
              ?>

              <div id="chart"></div>

              <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
              <script>
                var options = <?php echo $opciones_json; ?>;
                var chart = new ApexCharts(document.querySelector("#chart"), options);
                chart.render();
              </script>


            </div>
          </div>
        </div>
      </div>
      <!-- Diapositiva 3 -->
      <div class="carousel-item">
        <div class="row mt-4 justify-content-center">
          <div class="col-md-6">
            <div class="alert alert-secondary" role="alert">
              <!-- Tercer gráfico -->
              <?php
              // Función para obtener el navegador desde el User-Agent
              function obtenerNavegador($user_agent)
              {
                $navegadores = [
                  'Chrome' => 'Chrome',
                  'Mozilla' => 'Mozilla',
                  'Edge' => 'Edge',
                  'Safari' => 'Safari',
                  // Agregar más navegadores si es necesario
                ];

                foreach ($navegadores as $navegador => $nombre_navegador) {
                  if (stripos($user_agent, $navegador) !== false) {
                    return $nombre_navegador;
                  }
                }

                return null;
              }

              // Ejecutar el comando para obtener los User-Agent
              $comando = "cat ./access.log* ./access.log.old | grep $fecha | awk -F '\"' '{print \$6}'";

              // Ejecutar el comando y almacenar el resultado
              $resultado = shell_exec($comando);

              // Procesar el resultado para extraer los navegadores únicos
              $user_agents = explode("\n", trim($resultado));
              $navegadores_unicos = [];
              foreach ($user_agents as $user_agent) {
                // Extraer el navegador del User-Agent
                $navegador = obtenerNavegador($user_agent);
                if (!empty($navegador)) {
                  $navegadores_unicos[] = $navegador;
                }
              }

              // Contar el número de ocurrencias de cada navegador
              $navegadores_contados = array_count_values($navegadores_unicos);

              // Preparar los datos para el gráfico de barras
              $navegadores_unicos = array_keys($navegadores_contados);
              $conteos = array_values($navegadores_contados);

              // Configuración del gráfico de ApexCharts
              $opciones_grafico = [
                'series' => [
                  [
                    'data' => $conteos
                  ]
                ],
                'title' => [
                  'text' => 'Navegadores más utilizados para el Punto de Montaje',
                  'align' => 'center'
                ],

                'chart' => [
                  'type' => 'bar',
                  'height' => 350
                ],
                'plotOptions' => [
                  'bar' => [
                    'horizontal' => true,
                    
                  ]
                ],
                'dataLabels' => [
                  'enabled' => true
                ],
                'xaxis' => [
                  'categories' => $navegadores_unicos
                ],
                'annotations' => [
                  'xaxis' => [
                    [
                      'x' => 500,
                      'borderColor' => '#00E396',
                      'label' => [
                        'borderColor' => '#00E396',
                        'style' => [
                          'color' => '#fff',
                          'background' => '#00E396',
                        ],
                        'text' => 'X annotation',
                      ]
                    ]
                  ],
                  'yaxis' => [
                    [
                      'y' => 'July',
                      'y2' => 'September',
                      'label' => [
                        'text' => 'Y annotation'
                      ]
                    ]
                  ]
                ],
                'grid' => [
                  'xaxis' => [
                    'lines' => [
                      'show' => true
                    ]
                  ]
                ],
                'yaxis' => [
                  'reversed' => true,
                  'axisTicks' => [
                    'show' => true
                  ]
                ]
              ];

              // Convertir opciones a JSON
              $opciones_json = json_encode($opciones_grafico);
              ?>

              <div id="chartq"></div>

              <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
              <script>
                var options = <?php echo $opciones_json; ?>;
                var chart = new ApexCharts(document.querySelector("#chartq"), options);
                chart.render();
              </script>


            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Botones para diapositiva anterior y siguiente -->
    <button class="carousel-control-prev align-self-center" type="button"
      data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next align-self-center" type="button"
      data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>





  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    $(document).ready(function () {
      $('#datepicker').flatpickr();

      $('#searchButton').click(function () {
        // Aquí puedes implementar la lógica para manejar la búsqueda con el filtro seleccionado y la fecha seleccionada
        alert('Buscar haciendo clic en el botón');
      });
    });
  </script>



</body>
<footer class="footer mt-auto py-3 bg-white shadow-sm ">

  <div class="container">
  <br>
  <br>
    <div class="row">
      <div class="col-md-6">
        <span class="text-dark">Elaborado por Kutik Gomez - Clase de Ciencias de Datos</span>
      </div>
      <div class="col-md-6 text-md-end">
        <span class="text-dark">Contacto: kutikgomez@gmail.com - Teléfono: +593 993994147</span>
      </div>
    </div>
  </div>
</footer>
</html>
<!-- version 1 elaborado por RKGM-->