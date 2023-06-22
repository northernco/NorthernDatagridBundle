<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 * (c) Stanislav Turza
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\DataGridBundle\Grid\Export;

use APY\DataGridBundle\Grid\Column\ArrayColumn;
use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\Row;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\TemplateWrapper;

abstract class Export implements ExportInterface
{
    public const DEFAULT_TEMPLATE = '@APYDataGrid/blocks.html.twig';

    private string $title;

    private string $fileName;

    protected ?string $fileExtension = null;

    protected string $mimeType = 'application/octet-stream';

    private array $parameters = [];

    private ?array $templates = null;

    private Environment $twig;

    private TranslatorInterface $translator;

    private RouterInterface $router;

    private string $kernelCharset;

    private Grid $grid;

    private array $params = [];

    protected string $content = '';

    private string $charset;

    private ?string $role;

    public function __construct(
        string  $title,
        string  $fileName = 'export',
        array   $params = [],
        string  $charset = 'UTF-8',
        ?string $role = null
    )
    {
        $this->title = $title;
        $this->fileName = $fileName;
        $this->params = $params;
        $this->charset = $charset;
        $this->role = $role;
    }

    public function setTwig(Environment $twig): self
    {
        $this->twig = $twig;

        return $this;
    }

    public function setTranslator(TranslatorInterface $translator): self
    {
        $this->translator = $translator;

        return $this;
    }

    public function setRouter(RouterInterface $router): self
    {
        $this->router = $router;

        return $this;
    }

    public function setKernelCharset(string $kernelCharset): self
    {
        $this->kernelCharset = $kernelCharset;

        return $this;
    }

    public function getResponse(): Response
    {
        // Response
        $kernelCharset = $this->kernelCharset;
        if ($this->charset != $kernelCharset && function_exists('mb_strlen')) {
            $this->content = mb_convert_encoding($this->content, $this->charset, $kernelCharset);
            $filesize = mb_strlen($this->content, '8bit');
        } else {
            $filesize = strlen($this->content);
            $this->charset = $kernelCharset;
        }

        $headers = [
            'Content-Description' => 'File Transfer',
            'Content-Type' => $this->getMimeType(),
            'Content-Disposition' => sprintf('attachment; filename="%s"', $this->getBaseName()),
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public',
            'Content-Length' => $filesize,
        ];

        $response = new Response($this->content, 200, $headers);
        $response->setCharset($this->charset);
        $response->expire();

        return $response;
    }

    public function setContent(string $content = ''): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    protected function getGridData(Grid $grid): array
    {
        $result = [];

        $this->grid = $grid;

        if ($this->grid->isTitleSectionVisible()) {
            $result['titles'] = $this->getGridTitles();
        }

        $result['rows'] = $this->getGridRows();

        return $result;
    }

    protected function getRawGridData(Grid $grid): array
    {
        $result = [];
        $this->grid = $grid;

        if ($this->grid->isTitleSectionVisible()) {
            $result['titles'] = $this->getRawGridTitles();
        }

        $result['rows'] = $this->getRawGridRows();

        return $result;
    }

    protected function getFlatGridData(Grid $grid): array
    {
        $data = $this->getGridData($grid);

        $flatData = [];
        if (isset($data['titles'])) {
            $flatData[] = $data['titles'];
        }

        return array_merge($flatData, $data['rows']);
    }

    protected function getFlatRawGridData(Grid $grid): array
    {
        $data = $this->getRawGridData($grid);

        $flatData = [];
        if (isset($data['titles'])) {
            $flatData[] = $data['titles'];
        }

        return array_merge($flatData, $data['rows']);
    }

    protected function getGridTitles(): array
    {
        $titlesHTML = $this->renderBlock('grid_titles', ['grid' => $this->grid]);

        preg_match_all('#<th[^>]*?>(.*)?</th>#isU', $titlesHTML, $matches);

        if (empty($matches)) {
            preg_match_all('#<td[^>]*?>(.*)?</td>#isU', $titlesHTML, $matches);
        }

        if (empty($matches)) {
            new \Exception('Table header (th or td) tags not found.');
        }

        $titlesClean = array_map([$this, 'cleanHTML'], $matches[0]);

        $i = 0;
        $titles = [];

        foreach ($this->grid->getColumns() as $column) {
            if ($column->isVisible(true)) {
                if (!isset($titlesClean[$i])) {
                    throw new \OutOfBoundsException('There are more visible columns than titles found.');
                }
                $titles[$column->getId()] = $titlesClean[$i++];
            }
        }

        return $titles;
    }

    protected function getRawGridTitles(): array
    {
        $translator = $this->translator;

        $titles = [];
        foreach ($this->grid->getColumns() as $column) {
            if ($column->isVisible(true)) {
                $titles[] = utf8_decode($translator->trans(/* @Ignore */ $column->getTitle()));
            }
        }

        return $titles;
    }

    protected function getGridRows(): array
    {
        $rows = [];
        foreach ($this->grid->getRows() as $i => $row) {
            foreach ($this->grid->getColumns() as $column) {
                if ($column->isVisible(true)) {
                    $cellHTML = $this->getGridCell($column, $row);
                    $rows[$i][$column->getId()] = $this->cleanHTML($cellHTML);
                }
            }
        }

        return $rows;
    }

    protected function getRawGridRows(): array
    {
        $rows = [];
        foreach ($this->grid->getRows() as $i => $row) {
            foreach ($this->grid->getColumns() as $column) {
                if ($column->isVisible(true)) {
                    $rows[$i][$column->getId()] = $row->getField($column->getId());
                }
            }
        }

        return $rows;
    }

    protected function getGridCell(Column $column, Row $row): string
    {
        $values = $row->getField($column->getId());

        // Cast a datetime won't work.
        if ($column instanceof ArrayColumn || !is_array($values)) {
            $values = [$values];
        }

        $separator = $column->getSeparator();

        $block = null;
        $return = [];
        foreach ($values as $sourceValue) {
            $value = $column->renderCell($sourceValue, $row, $this->router);

            $id = $this->grid->getId();

            if (
                ($id != ''
                    && ($block !== null
                        || $this->hasBlock($block = 'grid_' . $id . '_column_' . $column->getRenderBlockId() . '_cell')
                        || $this->hasBlock($block = 'grid_' . $id . '_column_' . $column->getType() . '_cell')
                        || $this->hasBlock($block = 'grid_' . $id . '_column_' . $column->getParentType() . '_cell')
                    )
                )
                || $this->hasBlock($block = 'grid_' . $id . '_column_id_' . $column->getRenderBlockId() . '_cell')
                || $this->hasBlock($block = 'grid_' . $id . '_column_type_' . $column->getType() . '_cell')
                || $this->hasBlock($block = 'grid_' . $id . '_column_type_' . $column->getParentType() . '_cell')
                || $this->hasBlock($block = 'grid_column_' . $column->getRenderBlockId() . '_cell')
                || $this->hasBlock($block = 'grid_column_' . $column->getType() . '_cell')
                || $this->hasBlock($block = 'grid_column_' . $column->getParentType() . '_cell')
                || $this->hasBlock($block = 'grid_column_id_' . $column->getRenderBlockId() . '_cell')
                || $this->hasBlock($block = 'grid_column_type_' . $column->getType() . '_cell')
                || $this->hasBlock($block = 'grid_column_type_' . $column->getParentType() . '_cell')) {
                $html = $this->renderBlock($block, ['grid' => $this->grid, 'column' => $column, 'row' => $row, 'value' => $value, 'sourceValue' => $sourceValue]);
            } else {
                $html = $this->renderBlock('grid_column_cell', ['grid' => $this->grid, 'column' => $column, 'row' => $row, 'value' => $value, 'sourceValue' => $sourceValue]);
                $block = null;
            }

            // Fix blank separator. The <br /> will be removed by the HTML cleaner.
            if (false !== strpos($separator, 'br')) {
                $html = str_replace($separator, ',', $html);
            }

            $return[] = $html;
        }

        $value = implode($separator, $return);

        return $value;
    }

    protected function hasBlock(string $name): bool
    {
        foreach ($this->getTemplates() as $template) {
            if ($template->hasBlock($name, [])) {
                return true;
            }
        }

        return false;
    }

    protected function renderBlock(string $name, array $parameters): string
    {
        foreach ($this->getTemplates() as $template) {
            if ($template->hasBlock($name, [])) {
                return $template->renderBlock($name, array_merge($parameters, $this->params));
            }
        }

        throw new \InvalidArgumentException(sprintf('Block "%s" doesn\'t exist in grid template "%s".', $name, 'ee'));
    }

    protected function getTemplates(): array
    {
        if (empty($this->templates)) {
            $this->setTemplate($this->grid->getTemplate());
        }

        return $this->templates;
    }

    public function setTemplate(\Twig\TemplateWrapper|string|null $template): self
    {
        if (is_string($template)) {
            if (substr($template, 0, 8) === '__SELF__') {
                $this->templates = $this->getTemplatesFromString(substr($template, 8));
                $this->templates[] = $this->twig->load(static::DEFAULT_TEMPLATE);
            } else {
                $this->templates = $this->getTemplatesFromString($template);
            }
        } elseif ($this->templates === null) {
            $this->templates[] = $this->twig->load(static::DEFAULT_TEMPLATE);
        } else {
            throw new \Exception('Unable to load template');
        }

        return $this;
    }

    protected function getTemplatesFromString(string|TemplateWrapper $theme): array
    {
        $templates = [];

        $template = $this->twig->load($theme);
        while ($template instanceof \Twig\TemplateWrapper) {
            $templates[] = $template;
            $template = $template->getParent([]);
        }

        return $templates;
    }

    protected function cleanHTML(array|string $value): string
    {
        // Handle image
        $value = preg_replace('#<img[^>]*title="([^"]*)"[^>]*?/>#isU', '\1', $value);

        // Clean indent
        $value = preg_replace('/>[\s\n\t\r]*</', '><', $value);

        // Clean HTML tags
        $value = strip_tags($value);

        // Convert Special Characters in HTML
        $value = html_entity_decode($value, ENT_QUOTES);

        // Remove whitespace
        $value = preg_replace('/\s\s+/', ' ', $value);

        // Fix space
        $value = preg_replace('/\s,/', ',', $value);

        // Trim
        $value = trim($value);

        return $value;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileExtension(string $fileExtension): self
    {
        $this->fileExtension = $fileExtension;

        return $this;
    }

    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    public function getBaseName(): string
    {
        return $this->fileName . (isset($this->fileExtension) ? ".$this->fileExtension" : '');
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setCharset(string $charset): self
    {
        $this->charset = $charset;

        return $this;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function addParameter(string $name, string $value): self
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    public function getParameter(string $name): string
    {
        if (!$this->hasParameter($name)) {
            throw new \InvalidArgumentException(sprintf('The parameter "%s" must be defined.', $name));
        }

        return $this->parameters[$name];
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }
}
