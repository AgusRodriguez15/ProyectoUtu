	<?php
	class disponibilidad {
		public $IdDisponibilidad;
		public $IdServicio;
		public $FechaInicio;
		public $FechaFin;
		public $estado;

		public function __construct($IdDisponibilidad, $IdServicio, $FechaInicio, $FechaFin, $estado) {
			$this->IdDisponibilidad = $IdDisponibilidad;
			$this->IdServicio = $IdServicio;
			$this->FechaInicio = $FechaInicio;
			$this->FechaFin = $FechaFin;
			$this->estado = $estado;
		}

		public function getIdDisponibilidad() { return $this->IdDisponibilidad; }

		public function getIdServicio() { return $this->IdServicio; }
		public function setIdServicio($IdServicio) { $this->IdServicio = $IdServicio; }

		public function getFechaInicio() { return $this->FechaInicio; }
		public function setFechaInicio($FechaInicio) { $this->FechaInicio = $FechaInicio; }

		public function getFechaFin() { return $this->FechaFin; }
		public function setFechaFin($FechaFin) { $this->FechaFin = $FechaFin; }

		public function getEstado() { return $this->estado; }
		public function setEstado($estado) { $this->estado = $estado; }
	}
