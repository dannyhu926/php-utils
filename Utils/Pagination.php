<?php

class Pagination
{
    /**
     * 页面Url
     *
     * @var
     */
    public $url;

    /**
     * Url码参数名
     *
     * @var string
     */
    public $paramName = 'page';
    /**
     * 记录总数
     *
     * @var int
     */
    public $totalResult = 0;
    /**
     * 每页显示记录数
     *
     * @var int
     */
    public $limit = 10;
    /**
     * 当前页码
     *
     * @var int
     */
    public $page = 1;
    /**
     * 分页显示最多页码数
     *
     * @var int
     */
    public $maxPageCount = 10;
    /**
     * 是否采取ajax请求显示页面
     *
     * @var bool
     */
    public $isAjax = false;
    /**
     * ajax请求的js方法名，默认为pageAjax，当且仅当$isAjax为真触发。
     * 根据前端js，自行赋值修改
     *
     * @var string
     */
    public $ajaxActionName = 'pageAjax';

    /**
     * 首页显示文字
     *
     * @var string
     */
    public $firstPageText = '首页';
    public $firstPageClass = 'first';
    /**
     * 尾页显示文字
     *
     * @var string
     */
    public $lastPageText = '尾页';
    public $lastPageClass = 'last';
    /**
     * 下一页显示文字
     *
     * @var string
     */
    public $prevPageText = '上一页';
    public $prevPageClass = 'previous';
    /**
     * 上一页显示文字
     *
     * @var string
     */
    public $nextPageText = '下一页';
    public $nextPageClass = 'next';
    /**
     * 当前页码样式
     *
     * @var string
     */
    public $currentPageClass = 'active';
    /**
     * 禁用的样式
     *
     * @var string
     */
    public $disabledPageClass = 'disabled';
    /**
     * 链接模板
     *
     * @var string
     */
    public $linkTpl = '<li class="{attributes}"><a{href}>{text}</a></li>';
    /**
     * 分页显示模板
     *
     * @var string
     */
    public $htmlTpl = '<ul class="pagination pull-right">{first}{prev}{pages}{next}{last}</ul><div class="pageTotalInfo pull-left">{page}/{pageCount} 总记录数:{totalResult}</div>';

    /**
     * 构造函数
     */
    public function __construct($page, $totalResult) {
        $this->page = $page;
        $this->totalResult = $totalResult;
        empty($this->url) && $this->setUrl();
    }

    public function info() {
        $this->page = max(1, $this->page);
        $pageCount = max(1, ceil($this->totalResult / $this->limit));
        return [
            'current_page' => $this->page,
            'page_count' => $pageCount,
            'page_size' => $this->limit,
            'result_count' => $this->totalResult
        ];
    }

    /**
     * 设置记录条数
     *
     * @return mixed
     */
    public function setPageSize($page_size) {
        $this->limit = $page_size;
        return $this;
    }

    /**
     * 显示分页
     * 当前第1/453页 [首页] [上页] 1 2 3 4 5 6 7 8 9 10 [下页] [尾页]
     */
    public function show() {
        $this->page = max(1, $this->page);
        $pageCount = ceil($this->totalResult / $this->limit);

        if ($pageCount <= 0 || $this->page > $pageCount) return '';

        $search = array(
            '{first}',
            '{prev}',
            '{pages}',
            '{next}',
            '{last}',
            '{page}',
            '{totalResult}',
            '{pageCount}'
        );
        $replace = array(
            $this->getFirstPageLink($pageCount),
            $this->getPrevPageLink($pageCount),
            $this->getPages($pageCount),
            $this->getNextPageLink($pageCount),
            $this->getLastPageLink($pageCount),
            $this->page,
            $this->totalResult,
            $pageCount
        );

        $html = str_replace($search, $replace, $this->htmlTpl);
        return $html;
    }

    /**
     * 获取页面列表，以页面数字为单位显示
     *
     * @param $pageCount
     * @return string
     */
    public function getPages($pageCount) {
        $pages = '';
        $startPage = 1;
        $endPage = $pageCount > $this->maxPageCount ? $this->maxPageCount : $pageCount;
        if (($pageCount > $this->maxPageCount) && ($this->page >= $this->maxPageCount)) {
            $startPage = $this->page - intval($this->maxPageCount / 2);
            if ($this->page <= ($pageCount - $this->maxPageCount / 2)) {
                $endPage = $this->page + intval($this->maxPageCount / 2) - 1;
            } else {
                $endPage = $pageCount;
            }
        }
        for ($i = $startPage; $i <= $endPage; $i++) {
            $style = $i == $this->page ? $this->currentPageClass : '';
            $url = $this->getUrl($i);
            $pages .= $this->getLink($i, $url, $style);
        }

        return $pages;
    }

    /**
     * 获取首页html
     *
     * @param $pageCount    页面总数
     * @return mixed
     */
    public function getFirstPageLink($pageCount) {
        $firstPageUrl = $this->getUrl(1);
        if (1 == $this->page) {
            $firstPageUrl = '';
            $this->firstPageClass = $this->firstPageClass . " " . $this->disabledPageClass;
        }

        return $this->getLink($this->firstPageText, $firstPageUrl, $this->firstPageClass);
    }

    /**
     * 获取尾页html
     *
     * @param $pageCount    页面总数
     * @return mixed
     */
    public function getLastPageLink($pageCount) {
        $lastPageUrl = $this->getUrl($pageCount);
        if ($pageCount == $this->page) {
            $lastPageUrl = '';
            $this->lastPageClass = $this->lastPageClass . " " . $this->disabledPageClass;
        }

        return $this->getLink($this->lastPageText, $lastPageUrl, $this->lastPageClass);
    }

    /**
     * 获取上一页html
     *
     * @param $pageCount    页面总数
     * @return mixed
     */
    public function getPrevPageLink($pageCount) {
        $prevPageNumber = $this->page - 1;
        $prevPageUrl = '';
        if ($prevPageNumber > 0 && $pageCount) {
            $prevPageUrl = $this->getUrl($prevPageNumber);
        } else {
            $this->prevPageClass = $this->prevPageClass . " " . $this->disabledPageClass;
        }
        return $this->getLink($this->prevPageText, $prevPageUrl, $this->prevPageClass);
    }

    /**
     * 获取下一页html
     *
     * @param $pageCount    页面总数
     * @return mixed
     */
    public function getNextPageLink($pageCount) {
        $nextPageNumber = $this->page + 1;
        $nextPageUrl = '';
        if ($nextPageNumber <= $pageCount && $pageCount) {
            $nextPageUrl = $this->getUrl($nextPageNumber);
        } else {
            $this->nextPageClass = $this->nextPageClass . " " . $this->disabledPageClass;
        }
        return $this->getLink($this->nextPageText, $nextPageUrl, $this->nextPageClass);
    }

    /**
     * 组装链接html
     *
     * @param $text 显示文字
     * @param string $url 链接Url
     * @param string $style 标签样式
     * @return mixed
     */
    public function getLink($text, $url = '', $style = '') {
        if ($url) {
            if ($this->isAjax) {
                $_attrOnClick = $this->ajaxActionName ? ' onclick="' . $this->ajaxActionName . '(\'' . $url . '\')"' : '';
                $href = ' href="#" ';
                $attributes = ' ' . $style . $_attrOnClick . ' ';
            } else {
                $href = ' href="' . $url . '" ';
                $attributes = $style;
            }
        } else {
            $href = '';
            $attributes = $style;
        }

        $search = array(
            '{text}',
            '{href}',
            '{attributes}',
        );
        $replace = array(
            $text,
            $href,
            $attributes
        );

        $link = str_replace($search, $replace, $this->linkTpl);
        return $link;
    }

    /**
     * 获取指定分页页面Url
     *
     * @param $pageNumber 分页id
     * @return string
     */
    public function getUrl($pageNumber) {
        $urlObject = new UrlHelper($this->url);
        $urlObject->addParam($this->paramName, $pageNumber, true);

        return $urlObject->getUrl();
    }

    /**
     * 设置url，默认为当前访问Url
     *
     * @param string $url
     * @return void
     */
    public function setUrl($url = '') {
        $this->url = $url ? $url : UrlHelper::getCurrentUrl();
    }
}