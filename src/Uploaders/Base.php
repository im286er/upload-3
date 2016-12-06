<?php

namespace Recca0120\Upload\Uploaders;

use Illuminate\Http\Request;
use Recca0120\Upload\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Recca0120\Upload\Contracts\Uploader;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

abstract class Base implements Uploader
{
    protected $request;

    protected $filesystem;

    protected $path;

    public function __construct(Request $request, Filesystem $filesystem, $path = null)
    {
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->setPath($path);
    }

    public function setPath($path = null)
    {
        $this->path = is_null($path) === true ? sys_get_temp_dir() : $path;

        return $this;
    }

    protected function tmpfile($originalName)
    {
        $extension = $this->filesystem->extension($originalName);
        $token = $this->request->get('token');

        return $this->path.'/'.md5($originalName.$token).'.'.$extension;
    }

    protected function receive($output, $input, $start, $isCompleted = false, $headers = [])
    {
        $tmpfile = $this->tmpfile($output);
        $this->filesystem->appendStream($tmpfile.'.part', $input, $start);

        if ($isCompleted === false) {
            throw new ChunkedResponseException($headers);
        }

        $this->filesystem->move($tmpfile.'.part', $tmpfile);

        return $tmpfile;
    }

    /**
     * completedResponse.
     *
     * @method completedResponse
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function completedResponse(Response $response)
    {
        return $response;
    }
}