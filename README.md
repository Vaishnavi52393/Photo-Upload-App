# Assignment: Photo Upload App with hostPath Volumes

## Scenario

You've been given a simple PHP application that lets a user upload a photo.
For every upload, the app writes:
- the image file into `/data/photos/`
- a JSON metadata file (uploader, filename, size, timestamp) into `/data/metadata/`

A gallery page reads all the JSON metadata files and renders the photos with
their details. Your job is to deploy this app on Kubernetes using a
**hostPath volume**, and then explore what that choice actually means for
your data.

## Learning objectives

By the end of this task you should be able to:
- Explain what a `hostPath` volume is and how it differs from `emptyDir` and a `PersistentVolume`
- Mount a hostPath volume into a container and read/write to it from an app
- Explain why hostPath forces a pod to be tied to a specific node
- Describe the failure modes and security risks of hostPath in a real cluster

## Provided files

```
app/
  index.php      # gallery page (reads metadata JSON, displays photos)
  upload.php     # upload form + handler (writes photo + JSON metadata)
  photo.php      # serves image bytes safely
  Dockerfile
k8s/
  deployment.yaml   # has TODOs for you to fill in
  service.yaml
```

## Tasks

### 1. Build and load the image
Build the Docker image and make it available to your cluster (e.g. `docker build`,
then `kind load docker-image` / `minikube image load`, or push to a registry
your cluster can pull from).

```bash
docker build -t photo-app:latest ./app
```

### 2. Pick a node and a hostPath directory
Run `kubectl get nodes` and choose one node. Edit `k8s/deployment.yaml`:
- set `nodeSelector.kubernetes.io/hostname` to that node's name
- decide on a hostPath directory (default suggestion: `/mnt/k8s-data/photo-app`)

**Question to answer in your writeup:** why do we need `nodeSelector` here at all?
What would happen if we omitted it and just let the scheduler pick any node?

### 3. Deploy
```bash
kubectl apply -f k8s/deployment.yaml
kubectl apply -f k8s/service.yaml
kubectl get pods -o wide     # confirm it landed on the node you chose
```

### 4. Test the app
Visit `http://<node-ip>:30080/upload.php`, upload a couple of photos, then
check `http://<node-ip>:30080/index.php`. Confirm the metadata (uploader,
size, node name) shows up correctly.

Also inspect the host filesystem directly (SSH into the node, or `docker exec`
into a kind node) and confirm the JSON files and images are really sitting
there:
```bash
ls /mnt/k8s-data/photo-app/photos
cat /mnt/k8s-data/photo-app/metadata/*.json
```

### 5. Break it on purpose
Delete the pod and watch it get rescheduled:
```bash
kubectl delete pod -l app=photo-app
kubectl get pods -o wide
```

**Question to answer:** did the new pod land on the same node? If your cluster
has more than one node, try removing the `nodeSelector` and deleting the pod
repeatedly until it lands somewhere else. What happens to the gallery?

### 6. Reflect (short written answers, 3-5 sentences each)
1. Why did the photos "disappear" (or not) when the pod moved nodes?
2. Name two production risks of using hostPath for an application like this
   (think about node failure, multiple replicas, and security).
3. What Kubernetes object would you use instead of hostPath to get storage
   that follows the pod across nodes? Name it and describe how it differs.
4. hostPath containers can potentially read/write sensitive paths on the node
   (e.g. `/etc`, `/var/run/docker.sock`). Why is this a security concern, and
   what admission control feature could restrict it?

## Stretch goal (optional, for extra credit)

Convert the Deployment to use a `PersistentVolumeClaim` backed by a
`local` PersistentVolume (or your cluster's default StorageClass) instead of
`hostPath`, scale to 2 replicas, and explain in your writeup why this still
doesn't solve the "multiple replicas writing to the same directory" problem
without something like `ReadWriteMany` storage.

## Grading rubric

| Criteria | Points |
|---|---|
| App deployed successfully with hostPath volume | 25 |
| Upload + gallery working end-to-end (photo + JSON metadata) | 25 |
| Correct explanation of node-pinning / nodeSelector requirement | 15 |
| Reschedule experiment performed and results documented | 15 |
| Reflection questions answered thoughtfully | 20 |
| Stretch goal (PVC/local volume) | +10 bonus |
