function page_change_jump(e) {
    if (e) {
        if (e.value) {
            const Page = e.value;
            const URL_N = new URL(window.location);

            URL_N.searchParams.set("page", Page);

            window.location = URL_N
        }
    }
}