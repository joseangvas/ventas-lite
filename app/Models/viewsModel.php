<?php
	
	namespace app\models;

	class viewsModel{

		/*---------- Modelo obtener vista ----------*/
		protected function obtenerVistasModelo($vista){

			$listaBlanca=["dashboard","cashierNew","cashierList","cashierSearch","cashierUpdate","userNew","userList","userUpdate","userSearch","userPhoto","clientNew","clientList","clientSearch","clientUpdate","categoryNew","categoryList","categorySearch","categoryUpdate","productNew","productList","productSearch","productUpdate","productPhoto","productCategory","companyNew","saleNew","saleList","saleSearch","saleDetail","logOut"];
		/*----------  Funcion para ejecutar una consulta UPDATE preparada  ----------*/
		protected function actualizarDatos($tabla,$datos,$condicion){

			$query="UPDATE $tabla SET ";

			$C=0;
			foreach ($datos as $clave){
				if($C>=1){ $query.=","; }
				$query.=$clave["campo_nombre"]."=".$clave["campo_marcador"];
				$C++;
			}

			$query.=" WHERE ".$condicion["condicion_campo"]."=".$condicion["condicion_marcador"];

			$sql=$this->conectar()->prepare($query);

			foreach ($datos as $clave){
				$sql->bindParam($clave["campo_marcador"],$clave["campo_valor"]);
			}

			$sql->bindParam($condicion["condicion_marcador"],$condicion["condicion_valor"]);

			$sql->execute();

			return $sql;
		}


		/*---------- Funcion eliminar registro ----------*/
        protected function eliminarRegistro($tabla,$campo,$id){
            $sql=$this->conectar()->prepare("DELETE FROM $tabla WHERE $campo=:id");
            $sql->bindParam(":id",$id);
            $sql->execute();
            
            return $sql;
        }
        if(in_array($vista, $listaBlanca)){
            if(is_file("./app/views/content/".$vista."-view.php")){
                $contenido="./app/views/content/".$vista."-view.php";
            }else{
                $contenido="404";
            }
        }elseif($vista=="login" || $vista=="index"){
            $contenido="login";
        }else{
            $contenido="404";
        }
        return $contenido;
    }

}