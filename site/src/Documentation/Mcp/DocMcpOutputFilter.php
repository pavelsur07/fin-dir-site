<?php

declare(strict_types=1);

namespace App\Documentation\Mcp;

use Mcp\Schema\JsonRpc\Error;

/** Bounds every SDK response, including protocol errors and large request IDs. */
final class DocMcpOutputFilter extends \php_user_filter
{
    private string $pending = '';
    private bool $overflow = false;

    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while (null !== ($bucket = stream_bucket_make_writeable($in))) {
            $consumed += $bucket->datalen;
            $parts = explode("\n", $bucket->data);
            foreach ($parts as $index => $part) {
                if (!$this->overflow) {
                    if (strlen($this->pending) + strlen($part) + 1 > 1024 * 1024) {
                        $this->overflow = true;
                        $this->pending = '';
                    } else {
                        $this->pending .= $part;
                    }
                }
                if ($index < count($parts) - 1) {
                    $line = $this->overflow ? json_encode(Error::forInternalError('LIMIT_EXCEEDED: response exceeds 1 MiB.'), JSON_THROW_ON_ERROR) : $this->pending;
                    stream_bucket_append($out, stream_bucket_new($this->stream, $line."\n"));
                    $this->pending = '';
                    $this->overflow = false;
                }
            }
        }

        return PSFS_PASS_ON;
    }
}
