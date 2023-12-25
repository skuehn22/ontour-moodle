<?php

class bfpdf extends \pdf {
    /**
     * Overriding the footer function in TCPDF.
     */
    public function Footer() {
        $this->SetY(-25);
        $this->SetFont('helvetica', 'I', 10);
        $this->write(10, 'Powered byyyy ', '', 0, 'C', true, 0, false, false, 0);
    }
}
