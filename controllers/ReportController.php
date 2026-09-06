<?php

namespace Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use Models\AuditModel;
use Models\CatalogModel;
use Models\ReportModel;
use System\Core\Controller;

class ReportController extends Controller
{
    public function __construct(private ?ReportModel $reports = null)
    {
        $this->reports ??= new ReportModel();
    }

    public function index(): void
    {
        $this->view('reports/index', [
            'title' => 'Reports',
            'templates' => $this->reports->templates(),
            'summary' => $this->reports->summary(),
        ]);
    }

    public function pdf(string $code): never
    {
        $template = $this->reports->template($code);
        if (!$template) {
            http_response_code(404);
            exit('Report template not found.');
        }

        $data = $this->reports->data($code);
        $settings = (new CatalogModel())->settings();
        $title = $template['name'];
        ob_start();
        require BASE_PATH . '/views/reports/pdf.php';
        $html = (string) ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($template['paper_size'] ?: 'A4', $template['orientation'] ?: 'portrait');
        $dompdf->render();

        $fileName = preg_replace('/[^a-z0-9-]+/', '-', strtolower($code)) . '-' . date('Ymd-His') . '.pdf';
        $this->reports->logGeneration($code, $fileName);
        (new AuditModel())->record('report.generated', 'report', $code, ['file' => $fileName]);
        $dompdf->stream($fileName, ['Attachment' => true]);
        exit;
    }
}
