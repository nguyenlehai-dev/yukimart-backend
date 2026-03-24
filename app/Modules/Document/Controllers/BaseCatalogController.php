<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Document\Requests\BulkDestroyCatalogRequest;
use App\Modules\Document\Requests\BulkUpdateStatusCatalogRequest;
use App\Modules\Document\Requests\ChangeStatusCatalogRequest;
use App\Modules\Document\Requests\ImportCatalogRequest;
use App\Modules\Document\Requests\StoreCatalogRequest;
use App\Modules\Document\Requests\UpdateCatalogRequest;
use App\Modules\Document\Resources\CatalogCollection;
use App\Modules\Document\Resources\CatalogResource;
use App\Modules\Document\Services\CatalogService;
use Illuminate\Database\Eloquent\Model;

abstract class BaseCatalogController extends Controller
{
    public function __construct(protected CatalogService $catalogService) {}

    abstract protected function modelClass(): string;

    abstract protected function fileName(): string;

    abstract protected function successLabel(): string;

    public function stats(FilterRequest $request)
    {
        return $this->success($this->catalogService->stats($this->modelClass(), $request->all()));
    }

    public function index(FilterRequest $request)
    {
        $items = $this->catalogService->index($this->modelClass(), $request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(new CatalogCollection($items));
    }

    public function show(Model $model)
    {
        $model = $this->catalogService->show($model);

        return $this->successResource(new CatalogResource($model));
    }

    public function store(StoreCatalogRequest $request)
    {
        $model = $this->catalogService->store($this->modelClass(), $request->validated());

        return $this->successResource(new CatalogResource($model), 'Tạo '.$this->successLabel().' thành công!', 201);
    }

    public function update(UpdateCatalogRequest $request, Model $model)
    {
        $model = $this->catalogService->update($model, $request->validated());

        return $this->successResource(new CatalogResource($model), 'Cập nhật '.$this->successLabel().' thành công!');
    }

    public function destroy(Model $model)
    {
        $this->catalogService->destroy($model);

        return $this->success(null, 'Xóa '.$this->successLabel().' thành công!');
    }

    public function bulkDestroy(BulkDestroyCatalogRequest $request)
    {
        $this->catalogService->bulkDestroy($this->modelClass(), $request->ids);

        return $this->success(null, 'Xóa hàng loạt thành công!');
    }

    public function bulkUpdateStatus(BulkUpdateStatusCatalogRequest $request)
    {
        $this->catalogService->bulkUpdateStatus($this->modelClass(), $request->ids, $request->status);

        return $this->success(null, 'Cập nhật trạng thái hàng loạt thành công!');
    }

    public function changeStatus(ChangeStatusCatalogRequest $request, Model $model)
    {
        $model = $this->catalogService->changeStatus($model, $request->status);

        return $this->successResource(new CatalogResource($model), 'Đổi trạng thái thành công!');
    }

    public function export(FilterRequest $request)
    {
        return $this->catalogService->export($this->modelClass(), $request->all(), $this->fileName());
    }

    public function import(ImportCatalogRequest $request)
    {
        $this->catalogService->import($this->modelClass(), $request->file('file'));

        return $this->success(null, 'Import dữ liệu thành công.');
    }
}
