<footer class="ph3 ph5-ns pb4 cf w-100">
  <div class="mw9 center">
    <div class="flex f6">
      <div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-40-ns w-100 pa2 bt b--gray-monica pt3">
        @if (config('monica.support_email_address'))
          <ul>
            <li class="di mr2">{{ trans('app.footer_remarks') }} <a href="mailto:{{ config('monica.support_email_address') }}">{{ trans('app.footer_send_email') }}</a></li>
          </ul>
        @endif
      </div>

      <div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-20-ns w-100 pa2 tc bt b--gray-monica pt3">
        <img src="img/bondmemo.svg" width="24" height="24" alt="BondMemo">
      </div>

      <div class="{{ htmldir() == 'ltr' ? 'fl tr' : 'fr tl' }} w-40-ns w-100 pa2 bt b--gray-monica pt3">
        <ul>
          @if (config('app.source_url'))
            <li class="di"><a href="{{ config('app.source_url') }}">{{ trans('app.footer_source_code') }}</a></li>
          @endif
          @if (($version = config('monica.app_version')) !== '')
            <li class="di ml2">{{ trans('app.footer_version', ['version' => $version]) }}</li>
          @endif

          @include('partials.check')
        </ul>
      </div>
    </div>
  </div>
</footer>
