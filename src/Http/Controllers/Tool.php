<?php

namespace ClassicO\NovaMediaLibrary\Http\Controllers;

use ClassicO\NovaMediaLibrary\API;
use ClassicO\NovaMediaLibrary\Core\Crop;
use ClassicO\NovaMediaLibrary\Core\Helper;
use ClassicO\NovaMediaLibrary\Core\Model;
use ClassicO\NovaMediaLibrary\Core\Upload;
use ClassicO\NovaMediaLibrary\Http\Requests\CropFr;
use ClassicO\NovaMediaLibrary\Http\Requests\DeleteFr;
use ClassicO\NovaMediaLibrary\Http\Requests\FolderDelFr;
use ClassicO\NovaMediaLibrary\Http\Requests\FolderNewFr;
use ClassicO\NovaMediaLibrary\Http\Requests\GetFr;
use ClassicO\NovaMediaLibrary\Http\Requests\UpdateFr;
use ClassicO\NovaMediaLibrary\Http\Requests\UploadFr;
use Laravel\Nova\Http\Requests\NovaRequest;

class Tool
{
    public function get(GetFr $fr)
    {
        $preview = config('nova-media-library.resize.preview');

        $data = (new Model)->search(request());
        $data['array'] = collect($data['array'])->map(function ($item) use ($preview) {
            if (! $item->url) {
                $item = $item->toArray();
                $item['url'] = route('nml-private-file-admin', ['id' => $item['id']]);
            }
            $item['preview'] = Helper::preview($item, $preview);

            return $item;
        });

        return $data;
    }

    public function private(\Illuminate\Http\Request $request)
    {
        $item = Model::find($request->input('id'));
        $size = $request->input('img_size');

        if (! $item or ! $item->path) {
            return response()->noContent(404);
        }

        if (! in_array($size, data_get($item, 'options.img_sizes', []))) {
            $size = null;
        }

        return API::getPrivateFile($item->path, $size);
    }

    public function upload(UploadFr $fr)
    {
        $file = $fr->file('file');
        $file_name = " ({$file->getClientOriginalName()})";

        $upload = new Upload($file);

        abort_unless($upload->setType(), 422, __('Forbidden file format'));

        $upload->setWH();

        $upload->setFolder($fr->input('folder'));

        $upload->setPrivate();

        $upload->setFile();

        abort_unless($upload->checkSize(), 422, __('File size limit exceeded') . $file_name);

        $item = $upload->save();

        if ($item) {
            Crop::createSizes($item);

            abort_if($upload->noResize, 200, __('Unsupported image type for resizing, only the original is uploaded') . $file_name);

            $preview = config('nova-media-library.resize.preview');
            if (! $item->url) {
                $item = $item->toArray();
                $item['url'] = route('nml-private-file-admin', ['id' => $item['id']]);
            }
            $item['preview'] = Helper::preview($item, $preview);

            return $item;
        }

        abort(422, __('The file was not downloaded for unknown reasons') . $file_name);
    }

    public function delete(DeleteFr $fr)
    {
        $get = Model::find($fr->input('ids'));
        $delete = Model::whereIn('id', $fr->input('ids'))->delete();

        if (count($get) > 0) {
            $array = [];

            foreach ($get as $key) {
                $sizes = data_get($key, 'options.img_sizes', []);
                $array[] = Helper::folder($key->folder) . $key->name;

                if ($sizes) {
                    foreach ($sizes as $size) {
                        $name = explode('.', $key->name);
                        $array[] = Helper::folder($key->folder . implode('-' . $size . '.', $name));
                    }
                }
            }

            Helper::storage()->delete($array);
        }

        return ['status' => (bool) $delete];
    }

    public function update(UpdateFr $fr)
    {
        $item = Model::find($fr->input('id'));

        abort_unless($item, 422, __('Invalid id'));

        $item->title = $fr->input('title');
        $img_sizes = data_get($item->options, 'img_sizes', []);

        if ($fr->has('private') and config('nova-media-library.disk') === 's3') {
            $item->private = (bool) $fr->input('private');
            $visibility = Helper::visibility($item->private);

            Helper::storage()->setVisibility($item->path, $visibility);

            foreach ($img_sizes as $key) {
                Helper::storage()->setVisibility(API::getImageSize($item->path, $key), $visibility);
            }
        }

        $folder = $fr->input('folder');

        if ($folder and config('nova-media-library.store') === 'folders' and $folder !== $item->folder) {
            $private = Helper::isPrivate($folder);
            $array = [[$item->path, Helper::folder($folder . $item->name)]];

            foreach ($img_sizes as $key) {
                $name = API::getImageSize($item->name, $key);
                $array[] = [Helper::folder($item->folder . $name), Helper::folder($folder . $name)];
            }

            foreach ($array as $key) {
                Helper::storage()->move($key[0], $key[1]);

                if ($private != $item->private) {
                    Helper::storage()->setVisibility($key[1], Helper::visibility($private));
                }
            }

            $item->private = $private;
            $item->folder = Helper::replace('/' . $folder . '/');
            $item->lp = Helper::localPublic($item->folder, $private);
        }

        $item->save();

        return $item;
    }

    public function crop(CropFr $fr)
    {
        $crop = new Crop($fr->toArray());

        abort_unless($crop->form, 422, __('Crop module disabled'));

        abort_unless($crop->image, 422, __('Invalid request data'));

        $crop->make();

        if ($crop->save()) {
            return;
        }

        abort(422, __('The file was not downloaded for unknown reasons'));
    }

    public function folderNew(FolderNewFr $fr)
    {
        if (Helper::storage()->makeDirectory(Helper::folder($fr->input('base') . $fr->input('folder') . '/'))) {
            return ['folders' => Helper::directories()];
        }

        abort(422, __('Cannot manage folders'));
    }

    public function folderDel(FolderDelFr $fr)
    {
        if (Helper::storage()->deleteDirectory(Helper::folder($fr->input('folder')))) {
            return ['folders' => Helper::directories()];
        }

        abort(422, __('Cannot manage folders'));
    }

    public function folders(NovaRequest $request)
    {
        if ($request->get('searchQuery')) {
            $query = $request->get('searchQuery');
            $fetchedFolders = Helper::directories();

            return array_filter(
                $fetchedFolders,
                fn ($folder) => str_contains(strtolower((string) $folder), strtolower((string) $query)),
                ARRAY_FILTER_USE_KEY
            );
        }

        return Helper::directories();
    }
}
