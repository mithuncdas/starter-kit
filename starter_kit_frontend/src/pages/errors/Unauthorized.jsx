import { LockKeyIcon } from "@hugeicons/core-free-icons"
import ErrorPage from "./ErrorPage"

export default function Unauthorized() {
  return (
    <ErrorPage
      code={401}
      icon={LockKeyIcon}
      title="Unauthorized"
      description="You need to be signed in to view this page."
    />
  )
}
