<ol>
    <li>
        <p>
            <a href="https://www.ipara.com/" target="_blank">
                {{ trans('plugins/ipara::ipara.instructions.step_1', ['name' => 'iPara']) }}
            </a>
        </p>
    </li>
    <li>
        <p>
            {{ trans('plugins/ipara::ipara.instructions.step_2', ['name' => 'iPara']) }}
        </p>
    </li>
    <li>
        <p>
            {{ trans('plugins/ipara::ipara.instructions.step_3') }}
        </p>
    </li>
    <li>
        <p>
            {!!
                BaseHelper::clean(trans('plugins/ipara::ipara.instructions.step_4'))
            !!}
        </p>

        <code>{{ route('payments.ipara.webhook') }}</code>
    </li>
</ol>
