<?php
class mensaje
{
	public $IdMensaje;
	public $Contenido;
	public $Fecha;
	public $Estado;
	public $IdUsuarioEmisor;
	public $IdUsuarioReceptor;

	public function __construct($IdMensaje, $Contenido, $Fecha, $Estado, $IdUsuarioEmisor, $IdUsuarioReceptor)
	{
		$this->IdMensaje = $IdMensaje;
		$this->Contenido = $Contenido;
		$this->Fecha = $Fecha;
		$this->Estado = $Estado;
		$this->IdUsuarioEmisor = $IdUsuarioEmisor;
		$this->IdUsuarioReceptor = $IdUsuarioReceptor;
	}

	public function getIdMensaje()
	{
		return $this->IdMensaje;
	}

	public function getContenido()
	{
		return $this->Contenido;
	}
	public function setContenido($Contenido)
	{
		$this->Contenido = $Contenido;
	}

	public function getFecha()
	{
		return $this->Fecha;
	}
	public function setFecha($Fecha)
	{
		$this->Fecha = $Fecha;
	}

	public function getEstado()
	{
		return $this->Estado;
	}
	public function setEstado($Estado)
	{
		$this->Estado = $Estado;
	}

	public function getIdUsuarioEmisor()
	{
		return $this->IdUsuarioEmisor;
	}
	public function setIdUsuarioEmisor($IdUsuarioEmisor)
	{
		$this->IdUsuarioEmisor = $IdUsuarioEmisor;
	}

	public function getIdUsuarioReceptor()
	{
		return $this->IdUsuarioReceptor;
	}
	public function setIdUsuarioReceptor($IdUsuarioReceptor)
	{
		$this->IdUsuarioReceptor = $IdUsuarioReceptor;
	}
}
