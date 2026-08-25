<?php
namespace App\Libraries;

require_once __DIR__ . '/Fpdf/fpdf.php';

class FPDF_Protection extends \FPDF
{
    protected $encrypted = false;
    protected $Uvalue;
    protected $Ovalue;
    protected $Pvalue;
    protected $enc_obj_id;         // encryption object id

    public function SetProtection($permissions = [], $user_pass = '', $owner_pass = null)
    {
        $options = ['print' => 4, 'modify' => 8, 'copy' => 16, 'annot-forms' => 32];
        $protection = 192;
        foreach ($permissions as $perm) {
            if (!isset($options[$perm])) {
                $this->Error('Incorrect permission: ' . $perm);
            }
            $protection += $options[$perm];
        }
        if ($owner_pass === null) {
            $owner_pass = uniqid(rand());
        }
        $this->encrypted = true;
        $this->_generateencryptionkey($user_pass, $owner_pass, $protection);
    }

    protected function _putresources()
    {
        parent::_putresources();
        if ($this->encrypted) {
            $this->_newobj();
            $this->enc_obj_id = $this->n;
            $this->_out('<<');
            $this->_out('/Filter /Standard');
            $this->_out('/V 1');
            $this->_out('/R 2');
            $this->_out('/O (' . $this->Ovalue . ')');
            $this->_out('/U (' . $this->Uvalue . ')');
            $this->_out('/P ' . $this->Pvalue);
            $this->_out('>>');
            $this->_out('endobj');
        }
    }

    protected function _puttrailer()
    {
        parent::_puttrailer();
        if ($this->encrypted) {
            $this->_out('/Encrypt ' . $this->enc_obj_id . ' 0 R');
            $this->_out('/ID [()()]');
        }
    }

    protected function _generateencryptionkey($user_pass, $owner_pass, $protection)
    {
        $this->Uvalue = md5($user_pass);
        $this->Ovalue = md5($owner_pass);
        $this->Pvalue = -$protection;
    }
}
