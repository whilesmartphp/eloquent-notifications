<?php

namespace Whilesmart\Notifications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Whilesmart\Notifications\Support\Preferences;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'channels' => ['sometimes', 'array'],
            'channels.*' => ['boolean'],
            'types' => ['sometimes', 'array'],
            'types.*' => ['boolean'],
            'type_channels' => ['sometimes', 'array'],
            'type_channels.*' => ['array'],
            'type_channels.*.*' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $preferences = app(Preferences::class);
            foreach (array_keys((array) $this->input('channels', [])) as $channel) {
                if ($preferences->channelKey((string) $channel) === null || ! isset($preferences->channels()[$channel])) {
                    $validator->errors()->add("channels.{$channel}", 'This channel cannot be switched off.');
                }
            }
            foreach (array_keys((array) $this->input('types', [])) as $type) {
                if ($preferences->typeKey((string) $type) === null) {
                    $validator->errors()->add("types.{$type}", 'This notification type cannot be switched off.');
                }
            }
            foreach ((array) $this->input('type_channels', []) as $type => $channels) {
                foreach (array_keys((array) $channels) as $channel) {
                    if ($preferences->typeChannelKey((string) $type, (string) $channel) === null
                        || ! in_array($channel, $preferences->channelsFor((string) $type), true)) {
                        $validator->errors()->add("type_channels.{$type}.{$channel}", 'This notification type cannot be switched off on this channel.');
                    }
                }
            }
        }];
    }
}
