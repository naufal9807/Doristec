<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dokumen extends Member_Controller{

  public function __construct()
  {
    parent::__construct();
    $this->load->model('Dokumen_user_model');
  }

  function index()
  {
    // load data
    $data['view'] = $this->Dokumen_user_model->get_all('dokumen_user.id_user',$this->session->userdata('logged_in')['id_user']);

    $this->render['content'] = $this->load->view('member/dokumen/index', $data, TRUE);

		$this->render['desc'] = "Dokumen anda";

		$this->load->view('member/template', $this->render);
  }

  public function view($id)
  {
    $data['view'] = $this->Dokumen_user_model->select_by_id($id);

    $this->render['content'] = $this->load->view('member/dokumen/detail', $data, TRUE);
    $this->render['desc'] = "Detail dokumen anda";

    $this->load->view('member/template', $this->render);
  }

  public function edit($id)
  {
    $this->load->model('Tahun_model');

    if (!empty($_POST)) {

      //form validation
      $this->form_validation->set_rules('judul', 'Judul', 'trim|required');
      $this->form_validation->set_rules('ringkasan', 'Ringkasan', 'required');

      if (!$this->form_validation->run()) {
        $this->session->set_flashdata('error_message', validation_errors());
        redirect('dokumen/edit/'. $id, 'refresh');
      }else {
        $data['judul']        = $this->input->post('judul');
        $data['tahun']        = $this->input->post('tahun');
        $data['ringkasan']    = $this->input->post('ringkasan');
        $data['keyword']      = $this->input->post('keyword');
        $data['prodi']        = $this->input->post('prodi');
        $data['updated_at']   = date('Y-m-d H:i:s');
        $data['id_user']      = $this->session->userdata('logged_in')['id_user'];

        $this->Dokumen_user_model->update($data, $id);
        $this->session->set_flashdata('success_message', 'Edit dokumen berhasil');
        redirect('dokumen','refresh');
      }
    }

    $data['view'] = $this->Dokumen_user_model->select_by_id($id);
    $data['tahun'] = $this->Tahun_model->select_all();

    $this->render['content'] = $this->load->view('member/dokumen/edit', $data, TRUE);
    $this->render['desc'] = "Edit Dokumen";

    $this->load->view('member/template', $this->render);
  }

  public function editfile($id)
  {
    $this->load->library('pdf2text');
    if ($this->input->post('submit')) {

      // //upload file config
      $path = 'assets/uploads/doc/';
      $config['upload_path'] = $path;
      $config['allowed_types'] = 'doc|docx|pdf';
      $config['max_size'] = 55000;
      $config['encrypt_name'] = TRUE;

      $this->load->library('upload', $config);
      //uploading File
      if(!$this->upload->do_upload('filedoc'))
      {
        $this->session->set_flashdata('error_message', $this->upload->display_errors());
        redirect('member/dokumen/editfile/'.$id, 'refresh');
      }
      else
      {
        // delete doc File
        $path = 'assets/uploads/doc/';
        $record = $this->Dokumen_user_model->select_by_id($id);
        $filename1 = $record[0]['file'];
        unlink($path . $filename1);

        $data['file'] = $this->upload->data()['file_name'];

        // convert file to text
        $file = './assets/uploads/doc/'.$data['file'];
        // $file ='1.docx';
        $fileInformation = pathinfo($file);
        $extension = $fileInformation['extension'];
        if ($extension == 'pdf') { //convert pdf to text
          $converterpdf = new PDF2Text();
          $converterpdf->setFilename($file);
          $converterpdf->decodePDF();
          $data['penelitian'] = $converterpdf->output();
        }elseif ($extension == 'doc' || $extension == 'docx') { //convert docx to text
          $converter = new DocxToTextConversion($file);

          $data['penelitian'] = $converter->convertToText();

        } else {
          return 'Invalid File Type, please use pdf, doc or docx word document file.';
        }

        $this->session->set_flashdata('success_message', 'Edit file berhasil');
        $this->Dokumen_user_model->update($data, $id);

        echo "<script type='text/javascript'>window.location.href='http://localhost/frontendskripsi/dokumen';</script>";
      }
    }

    $data['view'] = $this->Dokumen_user_model->select_by_id($id);

    $this->render['content'] = $this->load->view('member/dokumen/editfile', $data, TRUE);
    $this->render['desc'] = "Edit File";

    $this->load->view('member/template', $this->render);
  }


  public function delete($id)
  {
    if ($id != null) {
      // delete doc File
      $path = 'assets/uploads/doc/';
      $record = $this->Dokumen_user_model->select_by_id($id);

      if (!empty($record[0]['file'])) {
        $filename1 = $record[0]['file'];
        unlink($path . $filename1);
      }
      // var_dump($record);
      // delete record

      $this->Dokumen_user_model->delete($id);
      redirect('dokumen');
    }else {
      echo "delete gagal";
    }
  }

  public function uploadDokumen()
  {
    $this->load->model('Tahun_model');
    $this->load->library('pdf2text');

    if (!empty($_POST)) {
      //form validation
      $this->form_validation->set_rules('judul', 'Judul', 'trim|required');
      $this->form_validation->set_rules('ringkasan', 'Ringkasan', 'required');

      if (!$this->form_validation->run()) {
        $this->session->set_flashdata('error_message', validation_errors());
        redirect('dokumen/upload', 'refresh');
      }else {

        $data['id_dokumen_user']      = random_string('alnum', 20) . date('dmY') . random_string('alnum', 20);
        $data['judul']        = $this->input->post('judul');
        $data['tahun']        = $this->input->post('tahun');
        $data['ringkasan']    = $this->input->post('ringkasan');
        $data['keyword']      = $this->input->post('keyword');
        $data['prodi']        = $this->input->post('prodi');
        $data['created_at']   = date('Y-m-d H:i:s');
        $data['id_user']      = $this->session->userdata('logged_in')['id_user'];

        //upload file config
        $path = 'assets/uploads/doc/';
        $config['upload_path'] = $path;
        $config['allowed_types'] = 'doc|docx|pdf';
        $config['max_size'] = 55000;
        $config['encrypt_name'] = TRUE;

        $this->load->library('upload', $config);

        //uploading File
        if(!$this->upload->do_upload('filedoc'))
        {
          $this->session->set_flashdata('error_message', $this->upload->display_errors());
          redirect('dokumen/upload', 'refresh');
        }
        else
        {
          $data['file'] = $this->upload->data()['file_name'];

          // convert file to text
          $file = './assets/uploads/doc/'.$data['file'];
          // $file ='1.docx';

          $fileInformation = pathinfo($file);
          $extension = $fileInformation['extension'];
          //convert pdf to text
          if ($extension == 'pdf') {
            $converterpdf = new PDF2Text();
            $converterpdf->setFilename($file);
            $converterpdf->decodePDF();
            $data['penelitian'] = $converterpdf->output();
            //convert word to text
          }elseif ($extension == 'doc' || $extension == 'docx') {
            $converter = new DocxToTextConversion($file);

            $data['penelitian'] = $converter->convertToText();

          } else {
            return 'Invalid File Type, please use pdf, doc or docx word document file.';
          }

          $this->session->set_flashdata('success_message', 'Tambah dokumen berhasil');
          $this->Dokumen_user_model->insert($data);
          echo "<script type='text/javascript'>window.location.href='http://localhost/frontendskripsi/dokumen';</script>";
          // redirect('dokumen','refresh');
        }
      }
    }

    // load data
    $data['tahun'] = $this->Tahun_model->get_statusAktif();
    $this->render['content'] = $this->load->view('member/dokumen/upload', $data, TRUE);

		$this->render['desc'] = "Upload Dokumen anda";

		$this->load->view('member/template', $this->render);
  }
}

class DocxToTextConversion
{
  private $document;

  public function __construct($DocxFilePath)
  {
    $this->document = $DocxFilePath;
  }

  public function convertToText()
  {
    if (isset($this->document) && !file_exists($this->document)) {
      return 'File Does Not exists';
    }

    $fileInformation = pathinfo($this->document);
    $extension = $fileInformation['extension'];
    if ($extension == 'doc' || $extension == 'docx') {
      if ($extension == 'doc') {
        return $this->extract_doc();
      } elseif ($extension == 'docx') {
        return $this->extract_docx();
      }
    } else {
      return 'Invalid File Type, please use doc or docx word document file.';
    }
  }

  private function extract_doc()
  {
    $fileHandle = fopen($this->document, 'r');
    $allLines = @fread($fileHandle, filesize($this->document));
    $lines = explode(chr(0x0D), $allLines);
    $document_content = '';
    foreach ($lines as $line) {
      $pos = strpos($line, chr(0x00));
      if (($pos !== false) || (strlen($line) == 0)) {
      } else {
        $document_content .= $line . ' ';
      }
    }
    $document_content = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/", '', $document_content);
    return $document_content;
  }

  private function extract_docx()
  {
    $document_content = '';
    $content = '';
    $path = base_url().'assets/uploads/doc/';

    $zip = zip_open($this->document);

    if (!$zip || is_numeric($zip)) {
      return false;
    }

    while ($zip_entry = zip_read($zip)) {
      if (zip_entry_open($zip, $zip_entry) == false) {
        continue;
      }

      if (zip_entry_name($zip_entry) != 'word/document.xml') {
        continue;
      }

      $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

      zip_entry_close($zip_entry);
    }

    zip_close($zip);

    $content = str_replace('</w:r></w:p></w:tc><w:tc>', ' ', $content);
    $content = str_replace('</w:r></w:p>', "\r\n", $content);
    $document_content = strip_tags($content);

    return $document_content;
  }
}
