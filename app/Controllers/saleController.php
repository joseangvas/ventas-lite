<?php

	namespace app\controllers;
	use app\models\mainModel;

	class saleController extends mainModel{

		/*---------- Controlador buscar codigo de producto ----------*/
        public function buscarCodigoVentaControlador(){

            /*== Recuperando codigo de busqueda ==*/
			$producto=$this->limpiarCadena($_POST['buscar_codigo']);

			/*== Comprobando que no este vacio el campo ==*/
			if($producto==""){
				return '
				<article class="message is-warning mt-4 mb-4">
					 <div class="message-header">
					    <p>¡Ocurrio un error inesperado!</p>
					 </div>
				    <div class="message-body has-text-centered">
				    	<i class="fas fa-exclamation-triangle fa-2x"></i><br>
						Debes de introducir el Nombre, Marca o Modelo del producto
				    </div>
				</article>';
				exit();
            }

            /*== Seleccionando productos en la DB ==*/
            $datos_productos=$this->ejecutarConsulta("SELECT * FROM producto WHERE (producto_nombre LIKE '%$producto%' OR producto_marca LIKE '%$producto%' OR producto_modelo LIKE '%$producto%') ORDER BY producto_nombre ASC");

            if($datos_productos->rowCount()>=1){

				$datos_productos=$datos_productos->fetchAll();

				$tabla='<div class="table-container mb-6"><table class="table is-striped is-narrow is-hoverable is-fullwidth"><tbody>';

				foreach($datos_productos as $rows){
					$tabla.='
					<tr class="has-text-left" >
                        <td><i class="fas fa-box fa-fw"></i> &nbsp; '.$rows['producto_nombre'].'</td>
                        <td class="has-text-centered">
                            <button type="button" class="button is-link is-rounded is-small" onclick="agregar_codigo(\''.$rows['producto_codigo'].'\')"><i class="fas fa-plus-circle"></i></button>
                        </td>
                    </tr>
                    ';
				}

				$tabla.='</tbody></table></div>';
				return $tabla;
			}else{
				return '<article class="message is-warning mt-4 mb-4">
					 <div class="message-header">
					    <p>¡Ocurrio un error inesperado!</p>
					 </div>
				    <div class="message-body has-text-centered">
				    	<i class="fas fa-exclamation-triangle fa-2x"></i><br>
						No hemos encontrado ningún producto en el sistema que coincida con <strong>“'.$producto.'”
				    </div>
				</article>';

				exit();
			}
        }


        /*---------- Controlador agregar producto a venta ----------*/
        public function agregarProductoCarritoControlador(){

            /*== Recuperando codigo del producto ==*/
            $codigo=$this->limpiarCadena($_POST['producto_codigo']);

            if($codigo==""){
                $alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"Debes de introducir el código de barras del producto",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }

            /*== Verificando integridad de los datos ==*/
            if($this->verificarDatos("[a-zA-Z0-9- ]{1,70}",$codigo)){
                $alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"El código de barras no coincide con el formato solicitado",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }

            /*== Comprobando producto en la DB ==*/
            $check_producto=$this->ejecutarConsulta("SELECT * FROM producto WHERE producto_codigo='$codigo'");
            if($check_producto->rowCount()<=0){
                $alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No hemos encontrado el producto con código de barras : '$codigo'",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }else{
                $campos=$check_producto->fetch();
            }

            /*== Codigo de producto ==*/
            $codigo=$campos['producto_codigo'];

            if(empty($_SESSION['datos_producto_venta'][$codigo])){

                $detalle_cantidad=1;

                $stock_total=$campos['producto_stock_total']-$detalle_cantidad;

                if($stock_total<0){
                    $alerta=[
						"tipo"=>"simple",
						"titulo"=>"Ocurrió un error inesperado",
						"texto"=>"Lo sentimos, no hay existencias disponibles del producto seleccionado",
						"icono"=>"error"
					];
					return json_encode($alerta);
			        exit();
                }

                $detalle_total=$detalle_cantidad*$campos['producto_precio_venta'];
                $detalle_total=number_format($detalle_total,MONEDA_DECIMALES,'.','');

                $_SESSION['datos_producto_venta'][$codigo]=[
                    "producto_id"=>$campos['producto_id'],
					"producto_codigo"=>$campos['producto_codigo'],
					"producto_stock_total"=>$stock_total,
					"producto_stock_total_old"=>$campos['producto_stock_total'],
                    "venta_detalle_precio_compra"=>$campos['producto_precio_compra'],
                    "venta_detalle_precio_venta"=>$campos['producto_precio_venta'],
                    "venta_detalle_cantidad"=>1,
                    "venta_detalle_total"=>$detalle_total,
                    "venta_detalle_descripcion"=>$campos['producto_nombre']
                ];

                $_SESSION['alerta_producto_agregado']="Se agrego <strong>".$campos['producto_nombre']."</strong> a la venta";
            }else{
                $detalle_cantidad=($_SESSION['datos_producto_venta'][$codigo]['venta_detalle_cantidad'])+1;

                $stock_total=$campos['producto_stock_total']-$detalle_cantidad;

                if($stock_total<0){
                    $alerta=[
						"tipo"=>"simple",
						"titulo"=>"Ocurrió un error inesperado",
						"texto"=>"Lo sentimos, no hay existencias disponibles del producto seleccionado",
						"icono"=>"error"
					];
					return json_encode($alerta);
			        exit();
                }

                $detalle_total=$detalle_cantidad*$campos['producto_precio_venta'];
                $detalle_total=number_format($detalle_total,MONEDA_DECIMALES,'.','');

                $_SESSION['datos_producto_venta'][$codigo]=[
                    "producto_id"=>$campos['producto_id'],
					"producto_codigo"=>$campos['producto_codigo'],
					"producto_stock_total"=>$stock_total,
					"producto_stock_total_old"=>$campos['producto_stock_total'],
                    "venta_detalle_precio_compra"=>$campos['producto_precio_compra'],
                    "venta_detalle_precio_venta"=>$campos['producto_precio_venta'],
                    "venta_detalle_cantidad"=>$detalle_cantidad,
                    "venta_detalle_total"=>$detalle_total,
                    "venta_detalle_descripcion"=>$campos['producto_nombre']
                ];

                $_SESSION['alerta_producto_agregado']="Se agrego +1 <strong>".$campos['producto_nombre']."</strong> a la venta. Total en carrito: <strong>$detalle_cantidad</strong>";
            }

            $alerta=[
				"tipo"=>"redireccionar",
				"url"=>APP_URL."saleNew/"
			];

			return json_encode($alerta);
        }


        /*---------- Controlador remover producto de venta ----------*/
        public function removerProductoCarritoControlador(){

            /*== Recuperando codigo del producto ==*/
            $codigo=$this->limpiarCadena($_POST['producto_codigo']);

            unset($_SESSION['datos_producto_venta'][$codigo]);

            if(empty($_SESSION['datos_producto_venta'][$codigo])){
				$alerta=[
					"tipo"=>"recargar",
					"titulo"=>"¡Producto removido!",
					"texto"=>"El producto se ha removido de la venta",
					"icono"=>"success"
				];
				
			}else{
				$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No hemos podido remover el producto, por favor intente nuevamente",
					"icono"=>"error"
				];
            }
            return json_encode($alerta);
        }


        /*---------- Controlador actualizar producto de venta ----------*/
        public function actualizarProductoCarritoControlador(){

            /*== Recuperando codigo & cantidad del producto ==*/
            $codigo=$this->limpiarCadena($_POST['producto_codigo']);
            $cantidad=$this->limpiarCadena($_POST['producto_cantidad']);

            /*== comprobando campos vacios ==*/
            if($codigo=="" || $cantidad==""){
            	$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No podemos actualizar la cantidad de productos debido a que faltan algunos parámetros de configuración",
                    "icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }

            /*== comprobando cantidad de productos ==*/
            if($cantidad<=0){
                $alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"Debes de introducir una cantidad mayor a 0",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }

            /*== Comprobando producto en la DB ==*/
            $check_producto=$this->ejecutarConsulta("SELECT * FROM producto WHERE producto_codigo='$codigo'");
            if($check_producto->rowCount()<=0){
                $alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No hemos encontrado el producto con código de barras : '$codigo'",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }else{
                $campos=$check_producto->fetch();
            }

            /*== comprobando producto en carrito ==*/
            if(!empty($_SESSION['datos_producto_venta'][$codigo])){

                if($_SESSION['datos_producto_venta'][$codigo]["venta_detalle_cantidad"]==$cantidad){
                    $alerta=[
						"tipo"=>"simple",
						"titulo"=>"Ocurrió un error inesperado",
						"texto"=>"No has modificado la cantidad de productos",
						"icono"=>"error"
					];
					return json_encode($alerta);
			        exit();
                }

                if($cantidad>$_SESSION['datos_producto_venta'][$codigo]["venta_detalle_cantidad"]){
                    $diferencia_productos="agrego +".($cantidad-$_SESSION['datos_producto_venta'][$codigo]["venta_detalle_cantidad"]);
                }else{
                    $diferencia_productos="quito -".($_SESSION['datos_producto_venta'][$codigo]["venta_detalle_cantidad"]-$cantidad);
                }


                $detalle_cantidad=$cantidad;

                $stock_total=$campos['producto_stock_total']-$detalle_cantidad;

                if($stock_total<0){
                    $alerta=[
						"tipo"=>"simple",
						"titulo"=>"Ocurrió un error inesperado",
						"texto"=>"Lo sentimos, no hay existencias suficientes del producto seleccionado. Existencias disponibles: ".($stock_total+$detalle_cantidad)."",
						"icono"=>"error"
					];
					return json_encode($alerta);
			        exit();
                }

                $detalle_total=$detalle_cantidad*$campos['producto_precio_venta'];
                $detalle_total=number_format($detalle_total,MONEDA_DECIMALES,'.','');

                $_SESSION['datos_producto_venta'][$codigo]=[
                    "producto_id"=>$campos['producto_id'],
					"producto_codigo"=>$campos['producto_codigo'],
					"producto_stock_total"=>$stock_total,
					"producto_stock_total_old"=>$campos['producto_stock_total'],
                    "venta_detalle_precio_compra"=>$campos['producto_precio_compra'],
                    "venta_detalle_precio_venta"=>$campos['producto_precio_venta'],
                    "venta_detalle_cantidad"=>$detalle_cantidad,
                    "venta_detalle_total"=>$detalle_total,
                    "venta_detalle_descripcion"=>$campos['producto_nombre']
                ];

                $_SESSION['alerta_producto_agregado']="Se $diferencia_productos <strong>".$campos['producto_nombre']."</strong> a la venta. Total en carrito <strong>$detalle_cantidad</strong>";

                $alerta=[
					"tipo"=>"redireccionar",
					"url"=>APP_URL."saleNew/"
				];

				return json_encode($alerta);
            }else{
                $alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No hemos encontrado el producto que desea actualizar en el carrito",
					"icono"=>"error"
				];
				return json_encode($alerta);
            }
        }


        /*---------- Controlador buscar cliente ----------*/
        public function buscarClienteVentaControlador(){

            /*== Recuperando termino de busqueda ==*/
			$cliente=$this->limpiarCadena($_POST['buscar_cliente']);

			/*== Comprobando que no este vacio el campo ==*/
			if($cliente==""){
				return '
				<article class="message is-warning mt-4 mb-4">
					 <div class="message-header">
					    <p>¡Ocurrio un error inesperado!</p>
					 </div>
				    <div class="message-body has-text-centered">
				    	<i class="fas fa-exclamation-triangle fa-2x"></i><br>
						Debes de introducir el Numero de documento, Nombre, Apellido o Teléfono del cliente
				    </div>
				</article>';
				exit();
            }

            /*== Seleccionando clientes en la DB ==*/
            $datos_cliente=$this->ejecutarConsulta("SELECT * FROM cliente WHERE (cliente_id!='1') AND (cliente_numero_documento LIKE '%$cliente%' OR cliente_nombre LIKE '%$cliente%' OR cliente_apellido LIKE '%$cliente%' OR cliente_telefono LIKE '%$cliente%') ORDER BY cliente_nombre ASC");

            if($datos_cliente->rowCount()>=1){

				$datos_cliente=$datos_cliente->fetchAll();

				$tabla='<div class="table-container mb-6"><table class="table is-striped is-narrow is-hoverable is-fullwidth"><tbody>';

				foreach($datos_cliente as $rows){
					$tabla.='
					<tr>
                        <td class="has-text-left" ><i class="fas fa-male fa-fw"></i> &nbsp; '.$rows['cliente_nombre'].' '.$rows['cliente_apellido'].' ('.$rows['cliente_tipo_documento'].': '.$rows['cliente_numero_documento'].')</td>
                        <td class="has-text-centered" >
                            <button type="button" class="button is-link is-rounded is-small" onclick="agregar_cliente('.$rows['cliente_id'].')"><i class="fas fa-user-plus"></i></button>
                        </td>
                    </tr>
                    ';
				}

				$tabla.='</tbody></table></div>';
				return $tabla;
			}else{
				return '
				<article class="message is-warning mt-4 mb-4">
					 <div class="message-header">
					    <p>¡Ocurrio un error inesperado!</p>
					 </div>
				    <div class="message-body has-text-centered">
				    	<i class="fas fa-exclamation-triangle fa-2x"></i><br>
						No hemos encontrado ningún cliente en el sistema que coincida con <strong>“'.$cliente.'”</strong>
				    </div>
				</article>';
				exit();
			}
        }


        /*---------- Controlador agregar cliente ----------*/
        public function agregarClienteVentaControlador(){

            /*== Recuperando id del cliente ==*/
			$id=$this->limpiarCadena($_POST['cliente_id']);

			/*== Comprobando cliente en la DB ==*/
			$check_cliente=$this->ejecutarConsulta("SELECT * FROM cliente WHERE cliente_id='$id'");
			if($check_cliente->rowCount()<=0){
				$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No hemos podido agregar el cliente debido a un error, por favor intente nuevamente",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
			}else{
				$campos=$check_cliente->fetch();
            }

			if($_SESSION['datos_cliente_venta']['cliente_id']==1){
                $_SESSION['datos_cliente_venta']=[
                    "cliente_id"=>$campos['cliente_id'],
                    "cliente_tipo_documento"=>$campos['cliente_tipo_documento'],
                    "cliente_numero_documento"=>$campos['cliente_numero_documento'],
                    "cliente_nombre"=>$campos['cliente_nombre'],
                    "cliente_apellido"=>$campos['cliente_apellido']
                ];

				$alerta=[
					"tipo"=>"recargar",
					"titulo"=>"¡Cliente agregado!",
					"texto"=>"El cliente se agregó para realizar una venta",
					"icono"=>"success"
				];
			}else{
				$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No hemos podido agregar el cliente debido a un error, por favor intente nuevamente",
					"icono"=>"error"
				];
            }
            return json_encode($alerta);
        }


        /*---------- Controlador remover cliente ----------*/
        public function removerClienteVentaControlador(){

			unset($_SESSION['datos_cliente_venta']);

			if(empty($_SESSION['datos_cliente_venta'])){
				$alerta=[
					"tipo"=>"recargar",
					"titulo"=>"¡Cliente removido!",
					"texto"=>"Los datos del cliente se han quitado de la venta",
					"icono"=>"success"
				];
			}else{
				$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No hemos podido remover el cliente, por favor intente nuevamente",
					"icono"=>"error"
				];	
			}
			return json_encode($alerta);
        }


        /*---------- Controlador registrar venta ----------*/
        public function registrarVentaControlador(){

            $caja=$this->limpiarCadena($_POST['venta_caja']);
            $venta_pagado=$this->limpiarCadena($_POST['venta_abono']);

            /*== Comprobando integridad de los datos ==*/
            if($this->verificarDatos("[0-9.]{1,25}",$venta_pagado)){
				$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"El total pagado por el cliente no coincide con el formato solicitado",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }

            if($_SESSION['venta_total']<=0 || (!isset($_SESSION['datos_producto_venta']) && count($_SESSION['datos_producto_venta'])<=0)){
				$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No ha agregado productos a esta venta",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }

            if(!isset($_SESSION['datos_cliente_venta'])){
				$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No ha seleccionado ningún cliente para realizar esta venta",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }


            /*== Comprobando cliente en la DB ==*/
			$check_cliente=$this->ejecutarConsulta("SELECT cliente_id FROM cliente WHERE cliente_id='".$_SESSION['datos_cliente_venta']['cliente_id']."'");
			if($check_cliente->rowCount()<=0){
				$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"No hemos encontrado el cliente registrado en el sistema",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }


            /*== Comprobando caja en la DB ==*/
            $check_caja=$this->ejecutarConsulta("SELECT * FROM caja WHERE caja_id='$caja'");
			if($check_caja->rowCount()<=0){
				$alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"La caja no está registrada en el sistema",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }else{
                $datos_caja=$check_caja->fetch();
            }


            /*== Formateando variables ==*/
            $venta_pagado=number_format($venta_pagado,MONEDA_DECIMALES,'.','');
            $venta_total=number_format($_SESSION['venta_total'],MONEDA_DECIMALES,'.','');

            $venta_fecha=date("Y-m-d");
            $venta_hora=date("h:i a");

            $venta_total_final=$venta_total;
            $venta_total_final=number_format($venta_total_final,MONEDA_DECIMALES,'.','');


            /*== Calculando el cambio ==*/
            if($venta_pagado<$venta_total_final){
                $alerta=[
					"tipo"=>"simple",
					"titulo"=>"Ocurrió un error inesperado",
					"texto"=>"Esta es una venta al contado, el total a pagar por el cliente no puede ser menor al total a pagar",
					"icono"=>"error"
				];
				return json_encode($alerta);
		        exit();
            }

            $venta_cambio=$venta_pagado-$venta_total_final;
            $venta_cambio=number_format($venta_cambio,MONEDA_DECIMALES,'.','');


            /*== Calculando total en caja ==*/
            $movimiento_cantidad=$venta_pagado-$venta_cambio;
            $movimiento_cantidad=number_format($movimiento_cantidad,MONEDA_DECIMALES,'.','');

            $total_caja=$datos_caja['caja_efectivo']+$movimiento_cantidad;
            $total_caja=number_format($total_caja,MONEDA_DECIMALES,'.','');
            