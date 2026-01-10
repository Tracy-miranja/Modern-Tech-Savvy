class LeaveTypeService {
  constructor(requestClient) {
    this.requestClient = requestClient;
    this.updateUrl = '/leave-types/update';
  }

  async fetch(data) {
    const res = await this.requestClient.post('/leave-types/fetch', data);
    return res.data;
  }

  async edit(data) {
    // Always post FormData with `slug` so backend validator passes
    const fd = new FormData();
    const slug =
      data?.slug ??
      data?.leave ??
      data?.leave_type_slug;

    if (!slug) throw new Error('Missing leave type slug');

    fd.append('slug', slug);
    const res = await this.requestClient.post('/leave-types/edit', fd);
    return res.data; // HTML fragment
  }

  async show(data) {
    const res = await this.requestClient.post('/leave-types/show', data);
    return res.data;
  }

  async save(data) {
    const res = await this.requestClient.post('/leave-types/store', data);
    toastr.success(res.message || 'Saved', 'Success');
  }

  async update(data) {
    // Guard: ensure we do NOT accidentally hit store when editing
    if (data instanceof FormData) {
      const slug = data.get('leave_type_slug') || data.get('slug') || data.get('leave');
      if (!slug || String(slug).trim().length === 0) {
        throw new Error('Missing leave_type_slug for update');
      }
      // ensure we sync policies by default so details reflect latest
      if (!data.has('sync_policies')) data.append('sync_policies', '1');
    }
    const res = await this.requestClient.post(this.updateUrl, data);
    toastr.info(res.message || 'Updated', 'Success');
  }

  async delete(data) {
    const res = await this.requestClient.post('/leave-types/destroy', data);
    toastr.info(res.message || 'Deleted', 'Success');
  }
}

export default LeaveTypeService;
