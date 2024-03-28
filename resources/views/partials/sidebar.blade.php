  <!-- ========== Left Sidebar Start ========== -->
  <div class="vertical-menu">

      <div data-simplebar class="h-100">

          <!--- Sidemenu -->
          <div id="sidebar-menu">
              <!-- Left Menu Start -->
              <ul class="metismenu list-unstyled" id="side-menu">

                  <li>
                      <a href="{{ route('home') }}">
                          <i data-feather="home"></i>
                          <span data-key="t-dashboard">Dashboard</span>
                      </a>
                  </li>

                  @if ($groups = module('groups'))
                      @foreach ($groups as $group_data)
                          <li class="menu-title" data-key="t-menu">{{ __($group_data->field_name) }}</li>
                          @if ($menus = $group_data->has_menu)
                              @foreach ($menus as $menu)
                                  @if ($menu->field_type == MenuType::Internal)
                                      <li>
                                          <a href="{{ $menu->field_url }}">
                                              <i data-feather="file-text"></i>
                                              <span data-key="t-dashboard">{{ $menu->field_name }}</span>
                                          </a>
                                      </li>
                                  @elseif($menu->field_type == MenuType::External)
                                      <li>
                                          <a href="{{ $menu->field_url }}">
                                              <i data-feather="file-text"></i>
                                              <span data-key="t-dashboard">{{ $menu->field_name }}</span>
                                          </a>
                                      </li>
                                  @elseif($menu->field_type == MenuType::Menu)
                                      <li>
                                          <a href="{{ $menu->field_action ? route($menu->field_action) : '' }}">
                                              <i data-feather="file-text"></i>
                                              <span data-key="t-dashboard">{{ $menu->field_name }}</span>
                                          </a>
                                      </li>
                                  @elseif($menu->field_type == MenuType::Group)
                                      <li>
                                          <a href="javascript: void(0);" class="has-arrow">
                                              <i data-feather="file-text"></i>
                                              <span data-key="t-pages">{{ $menu->field_name }}</span>
                                          </a>
                                          <ul class="sub-menu" aria-expanded="false">
                                              @if ($links = $menu->has_link)
                                                  @foreach ($links as $link)
                                                      <li>
                                                          <a href="{{ route($link->field_action) }}">{{ $link->field_name }}</a>
                                                      </li>
                                                  @endforeach
                                              @endif
                                          </ul>
                                      </li>
                                  @endif
                              @endforeach
                          @endif
                      @endforeach
                  @endif

              </ul>

          </div>
          <!-- Sidebar -->
      </div>
  </div>
  <!-- Left Sidebar End -->
